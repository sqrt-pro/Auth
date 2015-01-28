<?php

namespace SQRT\Auth;

class Exception extends \SQRT\Exception
{
  const NO_USER = 1;

  protected static $errors_arr = array(
    self::NO_USER => 'Пользователь не определен'
  );
}