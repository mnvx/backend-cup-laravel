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
    parts = {1, 'unsigned'}
})
box.space.profile:create_index('birth_date', {
    type = 'tree',
    parts = {6, 'unsigned'}
})
box.schema.user.grant('guest', 'read,write', 'space', 'profile')


box.schema.space.create('location', {
    id = 2000,
    field_count = 5,
    temporary = true
})
box.space.location:create_index('id', {
    type = 'hash',
    parts = {1, 'unsigned'}
})
box.space.location:create_index('country', {
    type = 'tree',
    parts = {3, 'string'}
})
box.space.location:create_index('distance', {
    type = 'tree',
    parts = {5, 'unsigned'}
})
box.schema.user.grant('guest', 'read,write', 'space', 'location')


box.schema.space.create('visit', {
    id = 3000,
    field_count = 5,
    temporary = true
})
box.space.visit:create_index('id', {
    type = 'hash',
    parts = {1, 'unsigned'}
})
box.space.visit:create_index('location', {
    type = 'tree',
    parts = {2, 'unsigned'}
})
box.space.visit:create_index('user', {
    type = 'tree',
    parts = {3, 'unsigned'}
})
box.space.visit:create_index('visited_at', {
    type = 'tree',
    parts = {4, 'unsigned'}
})
box.schema.user.grant('guest', 'read,write', 'space', 'visit')


box.schema.user.grant('guest', 'read', 'space', '_space')
