<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pesan ditampilkan ke customer, jadi selalu bahasa Indonesia.
 */
class OrderException extends RuntimeException {}
