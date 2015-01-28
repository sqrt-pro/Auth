<?php

namespace SQRT;

use SQRT\DB\Manager;
use SQRT\Auth\Exception as Ex;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class Auth
{
  protected $user;
  /** @var Manager */
  protected $manager;
  /** @var Cookie */
  protected $cookie;
  /** @var Request */
  protected $request;
  /** Имя переменной в сессии и cookies */
  protected $token_var = 'token';

  /** Поиск пользователя в БД по логину-паролю */
  abstract public function findUser($login, $password);

  /** Поиск пользователя в БД по токену */
  abstract public function findUserByToken($token);

  /** Сохранение токена в БД */
  abstract public function createToken($expire = null);

  /** Удаление токена из БД */
  abstract public function deleteToken($token);

  function __construct(Manager $manager, Request $request)
  {
    $this->manager = $manager;
    $this->request = $request;

    if ($token = $this->getToken()) {
      if (!$this->user = $this->findUserByToken($token)) {
        $this->cleanup();
      }
    }
  }

  /** Получить объект пользователя, если он залогинен */
  public function getUser()
  {
    return $this->user ?: false;
  }

  /** Авторизация пользователя по логину и паролю */
  public function login($login, $password, $remindme = true)
  {
    if ($this->user = $this->findUser($login, $password)) {
      $this->saveToken($remindme);
    }

    return $this->getUser();
  }

  /** Авторизация по объекту пользователя */
  public function loginUser($user, $remindme = true)
  {
    $this->user = $user;
    $this->saveToken($remindme);

    return $this->getUser();
  }

  /** @return static */
  public function logout()
  {
    if ($token = $this->getToken()) {
      $this->deleteToken($token);
    }

    $this->cleanup();

    return $this;
  }

  /** @return Cookie */
  public function getCookieForResponse()
  {
    return $this->cookie ?: false;
  }

  /** Имя переменной для хранения токена */
  public function getTokenVar()
  {
    return $this->token_var;
  }

  /** @return Request */
  protected function getRequest()
  {
    return $this->request;
  }

  /** @return Session */
  protected function getSession()
  {
    return $this->getRequest()->getSession();
  }

  /** @return Manager */
  protected function getManager()
  {
    return $this->manager;
  }

  /**
   * Создание и сохранение токена в сессии и формирование cookie
   * @return static
   */
  protected function saveToken($remindme)
  {
    if (!$u = $this->getUser()) {
      Ex::ThrowError(Ex::NO_USER);
    }

    $expire = strtotime('+1 month');
    $token  = $this->createToken($expire);

    $this->cookie = $remindme
      ? new Cookie($this->getTokenVar(), $token, $expire)
      : new Cookie($this->getTokenVar());

    $this->getSession()->set($this->getTokenVar(), $token);

    return $this;
  }

  /** Получение токена из сессии или cookies */
  protected function getToken()
  {
    return $this->getRequest()->cookies->get($this->getTokenVar(), $this->getSession()->get($this->getTokenVar()));
  }

  /** @return static */
  protected function cleanup()
  {
    $this->user   = null;
    $this->cookie = new Cookie($this->getTokenVar());

    $this->getSession()->remove($this->getTokenVar());

    return $this;
  }
}