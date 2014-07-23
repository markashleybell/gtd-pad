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

-- Insert test page
INSERT INTO pages
    (title, displayorder, user_id)
VALUES
    ('Test Page', 0, 1);