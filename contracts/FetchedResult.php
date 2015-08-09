<?php namespace Phrodo\Database\Contract;

use SeekableIterator;
use Countable;
use Iterator;

/**
 * Fetched result interface
 */
interface FetchedResult extends Result, Countable, Iterator, SeekableIterator
{
}
