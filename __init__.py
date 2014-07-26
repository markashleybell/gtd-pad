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


@app.route('/')
@app.route('/<int:id>')
@login_required
def page(id=1):
    pagedata = None

    with get_connection() as conn:
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        cur.execute('SELECT title FROM pages WHERE id = %s', [id])
        pagedata = cur.fetchone()

    return render_template('page.html', pagedata=pagedata)


@app.route('/api/v1/pages', methods=['GET'])
def get_pages():
    pages = None

    with get_connection() as conn:
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        cur.execute('SELECT * FROM pages WHERE deleted = False', [])
        pages = cur.fetchall()

    return ApiResponse(pages)


@app.route('/api/v1/pages/<int:id>', methods=['GET'])
def get_page(id):
    page = None

    with get_connection() as conn:
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        cur.execute('SELECT * FROM pages WHERE id = %s AND deleted = False', [id])
        page = cur.fetchone()

    if page is None:
        return ApiResponse(message='Not Found', status_code=404)

    return ApiResponse(page)


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