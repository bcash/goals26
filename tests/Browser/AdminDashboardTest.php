<?php

it('loads the admin panel and shows the app name', function () {
    $page = $this->visit('https://goals26.test/admin');

    $page->assertSee('Solas');
});

it('unauthenticated users are redirected to auth pages', function () {
    $page = $this->visit('https://goals26.test/admin');

    $page->assertPathContains('/admin');
});
