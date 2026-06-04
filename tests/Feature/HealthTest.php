<?php

declare(strict_types=1);

describe('health endpoint', function () {
    it('returns 200 with status ok', function () {
        $this->getJson('/api/v1/health')
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'checks', 'timestamp'])
            ->assertJsonPath('status', 'ok');
    });
});
