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
        Response.__init__(self, json.dumps({ 'status': status_code, 'message': message, 'payload': payload }, cls=JSONEncoder, indent=4, separators=(',', ': ')), status=status_code, mimetype='application/json')


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


# WEB


@app.route('/')
@app.route('/<int:id>')
@login_required
def index(id=1):
    pagedata = get_record('SELECT title FROM pages WHERE id = %s AND deleted = False AND user_id = %s',
                          [id, current_user.id])

    return render_template('page.html', pagedata=pagedata)


# PAGES API


@app.route('/api/v1/pages', methods=['GET'])
@login_required
def read_pages():
    pages = get_records('SELECT * FROM pages WHERE deleted = False AND user_id = %s ORDER BY displayorder',
                        [current_user.id])

    return ApiResponse(pages)


@app.route('/api/v1/pages', methods=['POST'])
@login_required
def create_page():
    title = request.json["title"]
    displayorder = request.json["displayorder"]
    pageid = execute_and_return_id('INSERT INTO pages (title, displayorder, user_id) VALUES (%s, %s, %s) RETURNING id',
                                   [title, displayorder, current_user.id])

    return ApiResponse({ 'id': pageid })


@app.route('/api/v1/pages/<int:id>', methods=['GET'])
@login_required
def read_page(id):
    page = get_record('SELECT * FROM pages WHERE id = %s AND deleted = False AND user_id = %s',
                      [id, current_user.id])

    if page is None:
        return ApiResponse(message='Not Found', status_code=404)

    return ApiResponse(page)


@app.route('/api/v1/pages/<int:id>', methods=['PUT'])
@login_required
def update_page(id):
    title = request.json["title"]
    displayorder = request.json["displayorder"]
    pageid = execute_and_return_id('UPDATE pages SET title = %s, displayorder = %s WHERE id = %s AND deleted = False AND user_id = %s RETURNING id',
                                   [title, displayorder, id, current_user.id])

    return ApiResponse({ 'id': pageid })


@app.route('/api/v1/pages/<int:id>', methods=['DELETE'])
@login_required
def delete_page(id):
    pageid = execute_and_return_id('DELETE FROM pages WHERE id = %s AND deleted = False AND user_id = %s RETURNING id',
                                   [id, current_user.id])
    
    return ApiResponse({ 'id': pageid })


# ITEMS API


@app.route('/api/v1/pages/<int:pageid>/items', methods=['GET'])
@login_required
def read_items(pageid):
    items = get_records('SELECT * FROM items WHERE deleted = False AND page_id = %s AND user_id = %s ORDER BY displayorder',
                        [pageid, current_user.id])

    return ApiResponse(items)


@app.route('/api/v1/pages/<int:pageid>/items', methods=['POST'])
@login_required
def create_item(pageid):
    title = request.json["title"]
    body = request.json["body"]
    itemtype_id = request.json["itemtype_id"]
    displayorder = request.json["displayorder"]
    itemid = execute_and_return_id('INSERT INTO items (title, body, displayorder, itemtype_id, page_id, user_id) VALUES (%s, %s, %s, %s, %s, %s) RETURNING id',
                                   [title, body, displayorder, itemtype_id, pageid, current_user.id])

    return ApiResponse({ 'id': itemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:id>', methods=['GET'])
@login_required
def read_item(pageid, id):
    item = get_record('SELECT * FROM items WHERE id = %s AND page_id = %s AND deleted = False AND user_id = %s',
                      [id, pageid, current_user.id])

    if item is None:
        return ApiResponse(message='Not Found', status_code=404)

    return ApiResponse(item)


@app.route('/api/v1/pages/<int:pageid>/items/<int:id>', methods=['PUT'])
@login_required
def update_item(pageid, id):
    title = request.json["title"]
    body = request.json["body"]
    page_id = request.json["page_id"]
    itemtype_id = request.json["itemtype_id"]
    displayorder = request.json["displayorder"]
    itemid = execute_and_return_id('UPDATE items SET title = %s, body = %s, itemtype_id = %s, page_id = %s, displayorder = %s WHERE id = %s AND page_id = %s AND deleted = False AND user_id = %s RETURNING id',
                                   [title, body, itemtype_id, page_id, displayorder, id, pageid, current_user.id])

    return ApiResponse({ 'id': itemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:id>', methods=['DELETE'])
@login_required
def delete_item(pageid, id):
    itemid = execute_and_return_id('DELETE FROM items WHERE id = %s AND page_id = %s AND deleted = False AND user_id = %s RETURNING id',
                                   [id, pageid, current_user.id])
    
    return ApiResponse({ 'id': itemid })


# LIST ITEMS API


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems', methods=['GET'])
@login_required
def read_listitems(pageid, itemid):
    listitems = get_records('SELECT * FROM listitems WHERE deleted = False AND item_id = %s AND user_id = %s ORDER BY displayorder',
                            [itemid, current_user.id])

    return ApiResponse(listitems)


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems', methods=['POST'])
@login_required
def create_listitem(pageid, itemid):
    body = request.json["body"]
    displayorder = request.json["displayorder"]
    listitemid = execute_and_return_id('INSERT INTO listitems (body, displayorder, item_id, user_id) VALUES (%s, %s, %s, %s) RETURNING id',
                                       [body, displayorder, itemid, current_user.id])

    return ApiResponse({ 'id': listitemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems/<int:id>', methods=['GET'])
@login_required
def read_listitem(pageid, itemid, id):
    listitem = get_record('SELECT * FROM listitems WHERE id = %s AND item_id = %s AND deleted = False AND user_id = %s',
                          [id, itemid, current_user.id])

    if listitem is None:
        return ApiResponse(message='Not Found', status_code=404)

    return ApiResponse(listitem)


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems/<int:id>', methods=['PUT'])
@login_required
def update_listitem(pageid, itemid, id):
    body = request.json["body"]
    item_id = request.json["item_id"]
    displayorder = request.json["displayorder"]
    itemid = execute_and_return_id('UPDATE listitems SET body = %s, item_id = %s, displayorder = %s WHERE id = %s AND item_id = %s AND deleted = False AND user_id = %s RETURNING id',
                                   [body, item_id, displayorder, id, itemid, current_user.id])

    return ApiResponse({ 'id': itemid })


@app.route('/api/v1/pages/<int:pageid>/items/<int:itemid>/listitems/<int:id>', methods=['DELETE'])
@login_required
def delete_listitem(pageid, itemid, id):
    itemid = execute_and_return_id('DELETE FROM listitems WHERE id = %s AND item_id = %s AND deleted = False AND user_id = %s RETURNING id',
                                   [id, itemid, current_user.id])
    
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