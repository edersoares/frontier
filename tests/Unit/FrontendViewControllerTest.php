<?php

test('flow using `FrontendViewController`', function () {
    $this->get('/view')
        ->assertStatus(200)
        ->assertSeeText('Frontier by View');
});
