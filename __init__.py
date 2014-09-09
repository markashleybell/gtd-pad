import json
import psycopg2
import psycopg2.extras
import psycopg2.extensions
from psycopg2.pool import ThreadedConnectionPool

psycopg2.extensions.register_type(psycopg2.extensions.UNICODE)
psycopg2.extensions.register_type(psycopg2.extensions.UNICODEARRAY)

from flask import Flask, Response, render_template, request, send_from_directory, \
     redirect, url_for, jsonify, flash
from flask.json import JSONEncoder
from flask.ext.login import LoginManager, current_user, login_required, \
     login_user, logout_user, UserMixin, confirm_login, fresh_login_required
from contextlib import contextmanager
from passlib.hash import sha512_crypt


@contextmanager
def get_connection():
    """Connection factory for pooled dbconnections"""
    conn = connection_pool.getconn()
    try:
        yield conn
    except Exception:
        conn.rollback()
        raise
    else:
        conn.commit()
    finally:
        connection_pool.putconn(conn)


# Set up application
app = Flask(__name__)
# Load configuration
app.config.from_pyfile('config.cfg')
app.secret_key = app.config['SECRET_KEY']

# Set up login manager
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'
login_manager.login_message = 'Please log in.'


class User(UserMixin):
    """User class based on Flask-Login UserMixin"""
    def __init__(self, id):
        self.id = id


class ApiResponse(Response):
    def __init__(self, payload=None, status_code=200, message='OK'):
        data = { 'status': status_code, 'message': message, 'payload': payload }
        encoded = json.dumps(data, cls=JSONEncoder, indent=4, separators=(',', ': '))
        Response.__init__(self, encoded, status=status_code, mimetype='application/json')


@login_manager.user_loader
def load_user(userid):
    """Callback to load user from db, called by Flask-Login"""
    user = None
    with get_connection() as conn:
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        cur.execute('SELECT id, username FROM users WHERE id = %s', [userid])
        user = cur.fetchone()
    if user is not None:
        return User(int(user['id']))
    return None


# Set up connection pool
connection_pool = ThreadedConnectionPool(1, 20, app.config['CONNECTION_STRING'])


def get_cursor(conn, sql, parameters):
    cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
    cur.execute(sql, parameters)
    return cur


def get_records(sql, parameters):
    output = None
    with get_connection() as conn:
        output = get_cursor(conn, sql, parameters).fetchall()
    return output


def get_record(sql, parameters):
    output = None
    with get_connection() as conn:
        output = get_cursor(conn, sql, parameters).fetchone()
    return output


def execute_and_return_id(sql, parameters):
    output = None
    with get_connection() as conn:
        output = get_cursor(conn, sql, parameters).fetchone()['id']
    return output


def get_api_fields(fields):
    full = request.args.get('full')
    return '*' if full == 'true' else fields


def get_select_single_query(fields, table):
    sql = """
          SELECT 
              {0}
          FROM 
              {1}
          WHERE 
              deleted = False 
          AND 
              user_id = %s 
          AND
              id = %s
          """
    return sql.format(fields, table)


def get_select_multiple_query(fields, table, filter=None, order='displayorder, created_at'):
    sql = """
          SELECT 
              {0}
          FROM 
              {1}
          WHERE 
              deleted = False 
          AND 
              user_id = %s 
          """

    if filter is not None:
        sql += """
               AND 
                   {2} = %s
               ORDER BY 
                   {3}
               """
        return sql.format(fields, table, filter, order)
    else:
        sql += """
               ORDER BY 
                   {2}
               """
        return sql.format(fields, table, order)


def get_insert_query(fields, table):
    sql = """
          INSERT INTO {0}
              ({1})
          VALUES
              ({2})
          RETURNING 
              id
          """
    return sql.format(table, ', '.join(fields), ', '.join(['%s' for f in fields]))


def get_update_query(fields, table):
    sql = """
          UPDATE
              {0}
          SET
              {1}
          WHERE
              deleted = False
          AND
              user_id = %s 
          AND
              id = %s
          RETURNING
              id
          """
    return sql.format(table, ', '.join([f + ' = %s' for f in fields]))


def get_delete_query(table):
    sql = """
          UPDATE
              {0}
          SET
              deleted = True
          WHERE
              user_id = %s 
          AND
              id = %s
          RETURNING
              id
          """
    return sql.format(table)


# WEB


@app.route('/')
@app.route('/<int:id>')
@login_required
def index(id=None):
    # If no page ID is passed in, just get the ID of the first page
    if id is None:
        id = execute_and_return_id('SELECT id FROM pages WHERE deleted = False AND user_id = %s ORDER BY displayorder LIMIT 1',
                                   [current_user.id])

    return render_template('page.html', id=id)


# PAGES API


@app.route('/api/v1/pages', methods=['GET'])
@login_required
def read_pages():
    fields = get_api_fields('id, title, displayorder')
    sql = get_select_multiple_query(fields, 'pages')
    pages = get_records(sql, [current_user.id])

    return ApiResponse(pages)


@app.route('/api/v1/pages', methods=['POST'])
@login_required
def create_page():
    title = request.json["title"]
    displayorder = request.json["displayorder"]
    sql = get_insert_query(['title', 'displayorder', 'user_id'], 'pages')
    pageid = execute_and_return_id(sql, [title, displayorder, current_user.id])

    return ApiResponse({ 'id': pageid })


@app.route('/api/v1/pages/<int:id>', methods=['GET'])
@login_required
def read_page(id):
    fields = get_api_fields('id, title, displayorder')
    sql = get_select_single_query(fields, 'pages')
    page = get_record(sql, [current_user.id, id])

    if page is None:
        return ApiResponse(message='Not Found', status_code=404)

    # TODO: Optimise data retrieval, one query per hierarchical level
    # If children have been requested
    if request.args.get('children') == 'true':
        # Get all the items for the page
        fields = get_api_fields('id, title, body, page_id, itemtype_id, displayorder')
        sql = get_select_multiple_query(fields, 'items', 'page_id')
        page['items'] = get_records(sql, [current_user.id, id])
        # Get all the list items for any lists
        for item in page['items']:
            fields = get_api_fields('id, body, completed, displayorder, item_id, {0} AS page_id'.format(id))
            sql = get_select_multiple_query(fields, 'listitems', 'item_id', 'completed, displayorder, created_at')
            if item['itemtype_id'] == 1:
                item['listitems'] = get_records(sql, [current_user.id, item['id']])

    return ApiResponse(page)


@app.route('/api/v1/pages/<int:id>', methods=['PUT'])
@login_required
def update_page(id):
    title = request.json["title"]
    displayorder = request.json["displayorder"]
    sql = get_update_query(['title', 'displayorder'], 'pages')
    pageid = execute_and_return_id(sql, [title, displayorder, current_user.id, id])

    return ApiResponse({ 'id': pageid })


@app.route('/api/v1/pages/<int:id>', methods=['DELETE'])
@login_required
def delete_page(id):
    sql = get_delete_query('pages')
    pageid = execute_and_return_id(sql, [current_user.id, id])
    
    return ApiResponse({ 'id': pageid })


# ITEMS API


@app.route('/api/v1/pages/<int:pageid>/items', methods=['GET'])
@login_required
def read_items(pageid):
    fields = get_api_fields('id, title, body, itemtype_id, displayorder')
    sql = get_select_multiple_query(fields, 'items', 'page_id')
    items = get_records(sql, [current_user.id, pageid])

    return ApiResponse(items)


@app.route('/api/v1/pages/<int:pageid>/items', methods=['POST'])
@login_required
def create_item(pageid):
    title = request.json["title"]
    body = request.json["body"]
    itemtype_id = request.json["itemtype_id"]
    displayorder = request.json["displayorder"]
    sql = get_insert_query(['title', 'body', 'displayorder', 'itemtype_id', 'page_id', 'user_id'], 'items')
    itemid = execute_and_return_id(sql, [title, body, displayorder, itemtype_id, pageid, current_user.id])

    return ApiResponse({ 'id': itemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:id>', methods=['GET'])
@login_required
def read_item(pageid, id):
    fields = get_api_fields('id, title, body, page_id, itemtype_id, displayorder')
    sql = get_select_single_query(fields, 'items')
    item = get_record(sql, [current_user.id, id])

    if item is None:
        return ApiResponse(message='Not Found', status_code=404)

    # TODO: Optimise data retrieval, one query per hierarchical level
    # If children have been requested
    if request.args.get('children') == 'true' and item['itemtype_id'] == 1:
        fields = get_api_fields('id, body, completed, displayorder, item_id, {0} AS page_id'.format(id))
        sql = get_select_multiple_query(fields, 'listitems', 'item_id', 'completed, displayorder, created_at')
        item['listitems'] = get_records(sql, [current_user.id, item['id']])

    return ApiResponse(item)


@app.route('/api/v1/pages/<int:pageid>/items/<int:id>', methods=['PUT'])
@login_required
def update_item(pageid, id):
    title = request.json["title"]
    body = request.json["body"]
    page_id = request.json["page_id"]
    displayorder = request.json["displayorder"]
    sql = get_update_query(['title', 'body', 'page_id', 'displayorder'], 'items')
    itemid = execute_and_return_id(sql, [title, body, page_id, displayorder, current_user.id, id])

    return ApiResponse({ 'id': itemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:id>', methods=['DELETE'])
@login_required
def delete_item(pageid, id):
    sql = get_delete_query('items')
    itemid = execute_and_return_id(sql, [current_user.id, id])
    
    return ApiResponse({ 'id': itemid })


# LIST ITEMS API


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems', methods=['GET'])
@login_required
def read_listitems(pageid, itemid):
    fields = get_api_fields('id, body, completed, displayorder')
    sql = get_select_multiple_query(fields, 'listitems', 'item_id')
    listitems = get_records(sql, [current_user.id, itemid])

    return ApiResponse(listitems)


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems', methods=['POST'])
@login_required
def create_listitem(pageid, itemid):
    body = request.json["body"]
    displayorder = request.json["displayorder"]
    sql = get_insert_query(['body', 'displayorder', 'item_id', 'user_id'], 'listitems')
    listitemid = execute_and_return_id(sql, [body, displayorder, itemid, current_user.id])

    return ApiResponse({ 'id': listitemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems/<int:id>', methods=['GET'])
@login_required
def read_listitem(pageid, itemid, id):
    fields = get_api_fields('id, body, completed, displayorder, item_id, {0} AS page_id'.format(pageid))
    sql = get_select_single_query(fields, 'listitems')
    listitem = get_record(sql, [current_user.id, id])

    if listitem is None:
        return ApiResponse(message='Not Found', status_code=404)

    return ApiResponse(listitem)


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems/<int:id>', methods=['PUT'])
@login_required
def update_listitem(pageid, itemid, id):
    body = request.json["body"]
    item_id = request.json["item_id"]
    displayorder = request.json["displayorder"]
    sql = get_update_query(['body', 'item_id', 'displayorder'], 'listitems')
    itemid = execute_and_return_id(sql, [body, item_id, displayorder, current_user.id, id])

    return ApiResponse({ 'id': itemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems/<int:id>', methods=['PATCH'])
@login_required
def update_listitem_partial(pageid, itemid, id):
    # Dynamically get the columns to update from the keys 
    # present in the request.json dictionary
    columns = [k for k in request.json]
    # Same for the updated data
    data = [request.json[k] for k in request.json]
    # Only update the columns we were passed data for
    sql = get_update_query(columns, 'listitems')
    itemid = execute_and_return_id(sql, data + [current_user.id, id])

    return ApiResponse({ 'id': itemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems/<int:id>', methods=['DELETE'])
@login_required
def delete_listitem(pageid, itemid, id):
    sql = get_delete_query('listitems')
    itemid = execute_and_return_id(sql, [current_user.id, id])
    
    return ApiResponse({ 'id': itemid })


@app.route('/login', methods=['GET'])
def login():
    return render_template('login.html', next=request.args['next'])
    

@app.route('/login', methods=['POST'])
def do_login():
    username = request.form['username']
    password = request.form['password']
    next = request.form['next']
    userdetails = None

    with get_connection() as conn:
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        cur.execute('SELECT id, password FROM users WHERE username = %s', [username])
        userdetails = cur.fetchone()
    
    if userdetails is not None and sha512_crypt.verify(password, userdetails['password']):
        login_user(User(userdetails['id']), remember=True)
        return redirect(next)

    return render_template('login.html', next=next, username=username)


@app.route('/logout', methods=['GET'])
def logout():
    logout_user();
    return redirect('/')


@app.errorhandler(404)
def page_not_found(e):
    if 'application/json' in request.headers['Accept']:
        return ApiResponse(message='Not Found', status_code=404)
    return render_template('404.html'), 404


@app.route('/robots.txt')
def static_from_root():
    return send_from_directory(app.static_folder, request.path[1:])


@app.route('/api-test-harness', methods=['GET'])
@login_required
def api_test_harness():
    return render_template('api-test-harness.html')


if __name__ == '__main__':
    app.run(debug=True, threaded=True)