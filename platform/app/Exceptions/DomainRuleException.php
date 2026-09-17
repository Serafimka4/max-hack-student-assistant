<?php

namespace App\Exceptions;

use RuntimeException;

/** Нарушение предметного правила: показывается пользователю как понятная ошибка (HTTP 422). */
class DomainRuleException extends RuntimeException {}
