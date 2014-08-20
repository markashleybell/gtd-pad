-- Users
CREATE TABLE users
(
  id serial NOT NULL,
  username character varying(256) NOT NULL,
  password character varying(256) NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  deleted_at timestamp with time zone NULL,
  CONSTRAINT pk_users PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

ALTER TABLE users OWNER TO postgres;


-- Item Types
CREATE TABLE itemtypes
(
  id serial NOT NULL,
  name character varying(256) NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  deleted_at timestamp with time zone NULL,
  CONSTRAINT pk_itemtypes PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

ALTER TABLE itemtypes OWNER TO postgres;


-- Pages
CREATE TABLE pages
(
  id serial NOT NULL,
  title character varying(256) NOT NULL,
  displayorder int NOT NULL DEFAULT -1,
  deleted boolean NOT NULL DEFAULT false,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  deleted_at timestamp with time zone NULL,
  user_id int NOT NULL REFERENCES users(id),
  CONSTRAINT pk_pages PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

ALTER TABLE pages OWNER TO postgres;


-- Items
CREATE TABLE items
(
  id serial NOT NULL,
  title character varying(256) NOT NULL,
  body text NULL,
  page_id int NOT NULL REFERENCES pages(id),
  itemtype_id int NOT NULL REFERENCES itemtypes(id),
  displayorder int NOT NULL DEFAULT -1,
  deleted boolean NOT NULL DEFAULT false,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  deleted_at timestamp with time zone NULL,
  user_id int NOT NULL REFERENCES users(id),
  CONSTRAINT pk_items PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

ALTER TABLE items OWNER TO postgres;


-- List Items
CREATE TABLE listitems
(
  id serial NOT NULL,
  body text NOT NULL,
  completed boolean NOT NULL DEFAULT false,
  item_id int NOT NULL REFERENCES items(id),
  displayorder int NOT NULL DEFAULT -1,
  deleted boolean NOT NULL DEFAULT false,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  deleted_at timestamp with time zone NULL,
  user_id int NOT NULL REFERENCES users(id),
  CONSTRAINT pk_listitems PRIMARY KEY (id)
)
WITH (OIDS=FALSE);

ALTER TABLE listitems OWNER TO postgres;


-- Function to update 'updated' timestamps
CREATE OR REPLACE FUNCTION update_updated_timestamp() RETURNS trigger AS $update_updated_timestamp$
    BEGIN
        NEW.updated_at = current_timestamp;
        RETURN NEW;
    END;
$update_updated_timestamp$ LANGUAGE plpgsql;

-- Triggers to call update_updated_timestamp on each table
--DROP TRIGGER update_users_updated_timestamp ON users;
CREATE TRIGGER update_users_updated_timestamp BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE PROCEDURE update_updated_timestamp();

--DROP TRIGGER update_users_updated_timestamp ON pages;
CREATE TRIGGER update_pages_updated_timestamp BEFORE UPDATE ON pages
    FOR EACH ROW EXECUTE PROCEDURE update_updated_timestamp();


-- Insert test user
INSERT INTO users
    (id, username, password)
VALUES
    (1, 'me@markashleybell.com', '$6$rounds=100000$WFAb1fBhbTt5G9hF$uWKzu5Y2mwIG4myHU9fBp3uKcYOHebWJNtNEbtUV7aDpB6AYcZ3cXnSBT8S9N5X5qL/5SgFk2MFRUhEE6s.1q/');

ALTER SEQUENCE users_id_seq RESTART WITH 2;


-- Insert item types
INSERT INTO itemtypes
    (id, name)
VALUES
    (1, 'List'),
    (2, 'Note');

ALTER SEQUENCE itemtypes_id_seq RESTART WITH 3;


-- Insert test page
INSERT INTO pages
    (id, title, displayorder, user_id)
VALUES
    (1, 'Test Page', 0, 1);

ALTER SEQUENCE pages_id_seq RESTART WITH 2;


-- Insert test items
INSERT INTO items
    (id, title, body, itemtype_id, page_id, displayorder, user_id)
VALUES
    (1, 'Test List', 'This is a test list.', 1, 1, 0, 1),
    (2, 'Test Note', 'This is a test note.', 2, 1, 1, 1);

ALTER SEQUENCE items_id_seq RESTART WITH 3;


-- Insert test list items
INSERT INTO listitems
    (id, body, item_id, displayorder, user_id)
VALUES
    (1, 'Test List Item 1', 1, 0, 1),
    (2, 'Test List Item 2', 1, 1, 1),
    (3, 'Test List Item 3', 1, 2, 1);

ALTER SEQUENCE listitems_id_seq RESTART WITH 4;
