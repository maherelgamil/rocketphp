<?php

declare(strict_types=1);

use MaherElGamil\Rocket\Dashboard\ActivityFeedWidget;

it('returns the correct type and title', function () {
    $arr = ActivityFeedWidget::make('Activity')->toArray();

    expect($arr['type'])->toBe('activity_feed');
    expect($arr['title'])->toBe('Activity');
});

it('returns empty items when no query set', function () {
    $arr = ActivityFeedWidget::make('Activity')->toArray();

    expect($arr['items'])->toBe([]);
});

it('maps rows using default columns', function () {
    $arr = ActivityFeedWidget::make('Activity')
        ->query(fn () => collect([
            ['description' => 'Post created', 'created_at' => '2026-04-19T12:00:00Z'],
            ['description' => 'User logged in', 'created_at' => '2026-04-19T11:00:00Z'],
        ]))
        ->toArray();

    expect($arr['items'])->toHaveCount(2);
    expect($arr['items'][0]['title'])->toBe('Post created');
    expect($arr['items'][0]['time'])->toBe('2026-04-19T12:00:00Z');
    expect($arr['items'][0]['icon'])->toBe('activity');
});

it('uses a custom title column', function () {
    $arr = ActivityFeedWidget::make('Activity')
        ->query(fn () => collect([['event_name' => 'Login', 'created_at' => null]]))
        ->titleColumn('event_name')
        ->toArray();

    expect($arr['items'][0]['title'])->toBe('Login');
});

it('uses a custom icon column', function () {
    $arr = ActivityFeedWidget::make('Activity')
        ->query(fn () => collect([['description' => 'Created', 'created_at' => null, 'type' => 'plus']]))
        ->iconColumn('type')
        ->toArray();

    expect($arr['items'][0]['icon'])->toBe('plus');
});

it('handles empty query results gracefully', function () {
    $arr = ActivityFeedWidget::make('Activity')
        ->query(fn () => collect([]))
        ->toArray();

    expect($arr['items'])->toBe([]);
});

it('works with stdClass rows from DB queries', function () {
    $row = new stdClass;
    $row->description = 'DB row';
    $row->created_at = '2026-04-19T10:00:00Z';

    $arr = ActivityFeedWidget::make('Activity')
        ->query(fn () => collect([$row]))
        ->toArray();

    expect($arr['items'][0]['title'])->toBe('DB row');
});
