<?php

it('responds on the health check route', function () {
    $this->get('/up')->assertOk();
});
