<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('quick-add-transaction');

    $component->assertSee('');
});
