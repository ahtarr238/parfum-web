<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pesan exception ini ditampilkan langsung ke customer, jadi selalu bahasa Indonesia.
 */
class CartException extends RuntimeException {}
