#!/usr/bin/env tarantool


box.cfg {
    listen = 3301,
    wal_mode = 'none',
}

box.sql.execute([[CREATE TABLE profile (
  id INTEGER PRIMARY KEY,
  email character varying(100) NOT NULL,
  first_name character varying(50) NOT NULL,
  last_name character varying(50) NOT NULL,
  gender character varying(1) NOT NULL,
  birth_date integer NOT NULL,
  CONSTRAINT profile_birth_date_check CHECK (birth_date >= -1262311200 AND birth_date < 915224400),
  CONSTRAINT profile_gender_check CHECK (gender IN ('m', 'f'))
);]])

box.sql.execute([[CREATE TABLE location (
  id INTEGER PRIMARY KEY,
  place text NOT NULL,
  country character varying(50) NOT NULL,
  city character varying(50) NOT NULL,
  distance integer NOT NULL
);]])

box.sql.execute([[CREATE TABLE visit (
  id INTEGER PRIMARY KEY,
  location integer NOT NULL,
  user integer NOT NULL,
  visited_at integer NOT NULL,
  mark smallint NOT NULL,
  CONSTRAINT visit_mark_check CHECK (mark >= 0 AND mark <= 5),
  CONSTRAINT visit_visited_at_check CHECK (visited_at >= 946674000 AND visited_at < 1420146000)
);]])

box.schema.user.grant('guest', 'read,write', 'space', '_space')
box.schema.user.grant('guest', 'read,write', 'space', '_index')
box.schema.user.grant('guest', 'read,write,create,drop,alter,execute', 'universe')