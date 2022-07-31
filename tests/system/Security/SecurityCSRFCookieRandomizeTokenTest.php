<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Security;

use CodeIgniter\Config\Factories;
use CodeIgniter\Cookie\Cookie;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockAppConfig;
use CodeIgniter\Test\Mock\MockSecurity;
use Config\Security as SecurityConfig;

/**
 * @internal
 */
final class SecurityCSRFCookieRandomizeTokenTest extends CIUnitTestCase
{
    /**
     * @var string CSRF randomized token
     */
    private string $randomizedToken = '8bc70b67c91494e815c7d2219c1ae0ab005513c290126d34d41bf41c5265e0f1';

    protected function setUp(): void
    {
        parent::setUp();

        $_COOKIE = [];

        $config                 = new SecurityConfig();
        $config->csrfProtection = Security::CSRF_PROTECTION_COOKIE;
        $config->tokenRandomize = true;
        Factories::injectMock('config', 'Security', $config);

        // Set Cookie value
        $security                            = new MockSecurity(new MockAppConfig());
        $_COOKIE[$security->getCookieName()] = $this->randomizedToken;

        $this->resetServices();
    }

    public function testTokenIsReadFromCookie()
    {
        $security = new MockSecurity(new MockAppConfig());

        $this->assertSame(
            $this->randomizedToken,
            $security->getToken()
        );
    }

    public function testCSRFVerifySetNewCookie()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['foo']              = 'bar';
        $_POST['csrf_test_name']   = $this->randomizedToken;

        $request = new IncomingRequest(new MockAppConfig(), new URI('http://badurl.com'), null, new UserAgent());

        $security = new Security(new MockAppConfig());

        $this->assertInstanceOf(Security::class, $security->verify($request));
        $this->assertLogged('info', 'CSRF token verified.');
        $this->assertCount(1, $_POST);

        /** @var Cookie $cookie */
        $cookie   = $this->getPrivateProperty($security, 'cookie');
        $newToken = $cookie->getValue();

        $this->assertNotSame($this->randomizedToken, $newToken);
        $this->assertSame(64, strlen($newToken));
    }
}
