<?php

test('ping endpoint works', function () {
    /** @phpstan-ignore-next-line */
    $response = $this->getJson('/api/v1/ping');
    $response->assertOk()->assertJson(['message' => 'pong']);
});
