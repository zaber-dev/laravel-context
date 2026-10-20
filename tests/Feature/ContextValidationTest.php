<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Tests\TestCase;

class ContextValidationTest extends TestCase
{
    public function test_context_unique_rule()
    {
        $workspace = Workspace::create(['name' => 'Validation WS']);
        
        Context::set($workspace);
        Task::create(['title' => 'Unique Task']);

        $validator = Validator::make(
            ['title' => 'Unique Task'],
            ['title' => Rule::contextUnique('tasks', 'title')]
        );

        $this->assertTrue($validator->fails());

        // Now test in another context
        $workspace2 = Workspace::create(['name' => 'Validation WS 2']);
        Context::set($workspace2);

        $validator2 = Validator::make(
            ['title' => 'Unique Task'],
            ['title' => Rule::contextUnique('tasks', 'title')]
        );

        $this->assertFalse($validator2->fails());
    }

    public function test_context_exists_rule()
    {
        $workspace = Workspace::create(['name' => 'Exists WS']);
        
        Context::set($workspace);
        Task::create(['title' => 'Existing Task']);

        $validator = Validator::make(
            ['title' => 'Existing Task'],
            ['title' => Rule::contextExists('tasks', 'title')]
        );

        $this->assertFalse($validator->fails());

        // Now test in another context
        $workspace2 = Workspace::create(['name' => 'Exists WS 2']);
        Context::set($workspace2);

        $validator2 = Validator::make(
            ['title' => 'Existing Task'],
            ['title' => Rule::contextExists('tasks', 'title')]
        );

        $this->assertTrue($validator2->fails());
    }
}
