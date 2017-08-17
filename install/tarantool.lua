#!/usr/bin/env tarantool


box.cfg {
    listen = 3301,
    wal_mode = 'none',
}

box.schema.space.create('profile', {
    id = 1000,
    field_count = 6,
    temporary = true,
})
box.space.profile:create_index('id', {
    type = 'hash',
    parts = {1, 'integer'}
})
box.space.profile:create_index('birth_date', {
    type = 'tree',
    unique = false,
    parts = {6, 'integer'}
})
box.schema.user.grant('guest', 'read,write', 'space', 'profile')


box.schema.space.create('location', {
    id = 2000,
    field_count = 5,
    temporary = true
})
box.space.location:create_index('id', {
    type = 'hash',
    parts = {1, 'integer'}
})
box.space.location:create_index('country', {
    type = 'tree',
    unique = false,
    parts = {3, 'string'}
})
box.space.location:create_index('distance', {
    type = 'tree',
    unique = false,
    parts = {5, 'integer'}
})
box.schema.user.grant('guest', 'read,write', 'space', 'location')


box.schema.space.create('visit', {
    id = 3000,
    field_count = 5,
    temporary = true
})
box.space.visit:create_index('id', {
    type = 'hash',
    parts = {1, 'integer'}
})
box.space.visit:create_index('location', {
    type = 'tree',
    unique = false,
    parts = {2, 'integer'}
})
box.space.visit:create_index('user', {
    type = 'tree',
    unique = false,
    parts = {3, 'integer'}
})
box.space.visit:create_index('visited_at', {
    type = 'tree',
    unique = false,
    parts = {4, 'integer'}
})
box.schema.user.grant('guest', 'read,write', 'space', 'visit')


box.schema.user.grant('guest', 'read', 'space', '_space')
