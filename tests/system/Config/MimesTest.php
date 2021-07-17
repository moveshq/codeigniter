<?php

namespace CodeIgniter\Config;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Mimes;

/**
 * @internal
 */
final class MimesTest extends CIUnitTestCase
{
    public function extensionsList()
    {
        return [
            'null' => [
                null,
                'xkadjflkjdsf',
            ],
            'single' => [
                'cpt',
                'application/mac-compactpro',
            ],
            'trimmed' => [
                'cpt',
                ' application/mac-compactpro ',
            ],
            'manyMimes' => [
                'csv',
                'text/csv',
            ],
            'mixedCase' => [
                'csv',
                'text/CSV',
            ],
        ];
    }

    //--------------------------------------------------------------------

    /**
     * @dataProvider extensionsList
     *
     * @param string|null $expected
     * @param string      $mime
     */
    public function testGuessExtensionFromType($expected, $mime)
    {
        $this->assertSame($expected, Mimes::guessExtensionFromType($mime));
    }

    //--------------------------------------------------------------------

    public function mimesList()
    {
        return [
            'null' => [
                null,
                'xalkjdlfkj',
            ],
            'single' => [
                'audio/midi',
                'mid',
            ],
            'many' => [
                'image/bmp',
                'bmp',
            ],
            'trimmed' => [
                'image/bmp',
                '.bmp',
            ],
            'mixedCase' => [
                'image/bmp',
                'BMP',
            ],
        ];
    }

    //--------------------------------------------------------------------

    /**
     * @dataProvider mimesList
     *
     * @param string|null $expected
     * @param string      $ext
     */
    public function testGuessTypeFromExtension($expected, $ext)
    {
        $this->assertSame($expected, Mimes::guessTypeFromExtension($ext));
    }

    //--------------------------------------------------------------------
}
