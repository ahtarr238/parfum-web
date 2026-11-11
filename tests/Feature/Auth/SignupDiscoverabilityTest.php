<?php

it('header untuk tamu menampilkan link Masuk dan Daftar', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $nav = substr($html, strpos($html, '<nav'), strpos($html, '</nav>') - strpos($html, '<nav'));

    expect($nav)->toContain('href="'.route('register').'"')
        ->and(substr_count($nav, 'href="'.route('register').'"'))->toBeGreaterThanOrEqual(2); // desktop + menu mobile
});

it('halaman login punya link ke pendaftaran', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Belum punya akun?')
        ->assertSee('href="'.route('register').'"', false);
});

it('memakai kata "Email", bukan "Surel"', function () {
    foreach (['/login', '/register', '/forgot-password'] as $page) {
        $this->get($page)->assertOk()->assertSee('Email')->assertDontSee('Surel')->assertDontSee('surel');
    }
});
