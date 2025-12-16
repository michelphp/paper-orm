<?php

namespace Test\Michel\PaperORM\Common;

use Michel\PaperORM\Parser\DSNParser;
use Michel\PaperORM\Tools\Slugger;
use Michel\UniTester\TestCase;

class SluggerTest extends TestCase
{

    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

    protected function execute(): void
    {
        $slugTests = [
            ['input' => ['Hello', 'World'], 'expected' => 'hello-world'],
            ['input' => ['Jean', 'Dupont'], 'expected' => 'jean-dupont'],
            ['input' => ['PHP', 'ORM', 'Slug'], 'expected' => 'php-orm-slug'],

            ['input' => ['   Hello   ', '   World   '], 'expected' => 'hello-world'],
            ['input' => ['   Multi   Space  ', '  Test  '], 'expected' => 'multi-space-test'],
            ['input' => ['  Hello  ', '', 'World  '], 'expected' => 'hello-world'],

            ['input' => ['À l\'ombre', 'du cœur'], 'expected' => 'a-l-ombre-du-coeur'],
            ['input' => ['Café', 'Crème'], 'expected' => 'cafe-creme'],
            ['input' => ['École', 'publique'], 'expected' => 'ecole-publique'],
            ['input' => ['Über', 'mensch'], 'expected' => 'uber-mensch'],

            ['input' => ['Hello!', 'World?'], 'expected' => 'hello-world'],
            ['input' => ['Slug@Email.com'], 'expected' => 'slug-email-com'],
            ['input' => ['Dollar$', 'Sign$'], 'expected' => 'dollar-sign'],
            ['input' => ['Hash#Tag'], 'expected' => 'hash-tag'],
            ['input' => ['C++', 'Language'], 'expected' => 'c-language'],
            ['input' => ['Node.js', 'Framework'], 'expected' => 'node-js-framework'],
            ['input' => ['100%', 'Working'], 'expected' => '100-working'],

            ['input' => ['Hello', null, 'World'], 'expected' => 'hello-world'],
            ['input' => ['0', 'Value'], 'expected' => '0-value'],
            ['input' => ['False', 'Start'], 'expected' => 'false-start'],
            ['input' => ['Hello', 123, 'World'], 'expected' => 'hello-123-world'],

            ['input' => ['snake_case_example'], 'expected' => 'snake-case-example'],
            ['input' => ['kebab-case-example'], 'expected' => 'kebab-case-example'],
            ['input' => ['mix_case_Example'], 'expected' => 'mix-case-example'],

            ['input' => ['Hello___World'], 'expected' => 'hello-world'],
            ['input' => ['Hello   ---   World'], 'expected' => 'hello-world'],
            ['input' => ['___Hello', 'World___'], 'expected' => 'hello-world'],

            ['input' => ['123', '456'], 'expected' => '123-456'],
            ['input' => ['Version', '2.0'], 'expected' => 'version-2-0'],
            ['input' => ['A+B=C'], 'expected' => 'a-b-c'],

            ['input' => ['NULL'], 'expected' => 'null'],
            ['input' => ['Éléphant', '', ''], 'expected' => 'elephant'],
            ['input' => [null, null, 'Test'], 'expected' => 'test'],
            ['input' => ['Long   String   With   Many   Spaces'], 'expected' => 'long-string-with-many-spaces'],
            ['input' => ['---Leading', 'And', 'Trailing---'], 'expected' => 'leading-and-trailing'],
            ['input' => ['This_is_a_very_long_text_with_many_parts'], 'expected' => 'this-is-a-very-long-text-with-many-parts'],
        ];


        foreach ($slugTests as $test) {
            $slug = Slugger::slugify($test['input']);
            $this->assertStrictEquals($test['expected'], $slug);
        }
    }

}
