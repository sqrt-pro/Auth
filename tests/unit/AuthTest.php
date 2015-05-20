<?php

use SQRT\DB\Manager;
use SQRT\Auth;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Request;

class authTest extends PHPUnit_Framework_TestCase
{
  function testAuth()
  {
    $m = new Manager();
    $s = new Session(new MockArraySessionStorage());
    $r = Request::create('/');
    $r->setSession($s);
    $a = new TestAuth($m, $r);

    $this->assertFalse($a->getUser(), 'Пользователь не залогинен');

    $t = $a->login('admin', 1234);

    $this->assertInstanceOf('TestUser', $a->getUser(), 'Объект пользователя');
    $this->assertEquals('1-abc', $t, 'Токен получен');
    $this->assertEquals('1-abc', $s->get($a->getTokenVar()), 'Токен сохранен в сессии');
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $a->getCookieForResponse(), 'Подготовлены куки');

    $a->logout();

    $this->assertFalse($a->getUser(), 'Нет пользователя');
    $this->assertFalse($s->has($a->getTokenVar()), 'Сессия очистилась');

    $u = new TestUser();
    $u->addRole('guest');
    $u->setId(2);

    $t = $a->loginUser($u);

    $this->assertEquals($u, $a->getUser(), 'Пользователь авторизован');
    $this->assertEquals('2-abc', $t, 'Токен получен');
    $this->assertEquals('2-abc', $s->get($a->getTokenVar()), 'Токен сохранен в сессии');

    $c = $a->getCookieForResponse();
    $this->assertEquals($a->getTokenVar(), $c->getName(), 'Имя переменной в сессии');
    $this->assertEquals('2-abc', $c->getValue(), 'Значение токена');

    $a = new TestAuth($m, $r);
    $this->assertInstanceOf('TestUser', $a->getUser(), 'Пользователь залогинился из сессии');

    $s->remove($a->getTokenVar());

    $r = Request::create('/', 'GET', array(), array($c->getName() => $c->getValue()));
    $r->setSession($s);

    $a = new TestAuth($m, $r);
    $this->assertInstanceOf('TestUser', $a->getUser(), 'Пользователь залогинился из cookies');
  }
}

class TestAuth extends Auth
{
  public function findUser($login, $password)
  {
    if ($login == 'admin') {
      return $this->makeAdmin();
    }

    return false;
  }

  public function findUserByToken($token)
  {
    if ($token == '2-abc') {
      return $this->makeAdmin();
    }

    return false;
  }

  protected function makeAdmin()
  {
    $u = new TestUser();
    $u->addRole('admin');

    return $u;
  }

  public function createToken($expire = null)
  {
    return $this->getUser()->getId() . '-abc';
  }

  public function deleteToken($token)
  {
    // Nothing
  }
}

class TestUser
{
  protected $id = 1;
  protected $roles = array();
  protected $tokens = array();

  public function hasRole($role)
  {
    return array_key_exists($role, $this->roles);
  }

  public function addRole($role)
  {
    $this->roles[$role] = true;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }
}