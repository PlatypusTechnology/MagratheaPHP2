<?php

namespace Magrathea2;

#######################################################################################
####
####	MAGRATHEA PHP
####	v. 2.0
####
####	Magrathea by Paulo Henrique Martins
####	Platypus technology
####
#######################################################################################
####
####	Pagination result object.
####	Returned by MagratheaModelControl::GetPagination() and recognized by
####	MagratheaApi::ReturnSuccess() to build a paginated JSON envelope
####	({success, data, page, count, has_more, total?}).
####
####	created: 2026-07 by Paulo Martins
####
#######################################################################################

class MagratheaPagination {

	/** @var array<object> List of objects for this page. */
	public $data;
	/** @var int Page requested (0 = first). */
	public int $page;
	/** @var int Number of items returned in this page. */
	public int $count;
	/** @var int|null Total number of rows across all pages, if known. */
	public ?int $total;
	/** @var bool Whether there is at least one more page after this one. */
	public bool $hasMore;

	/**
	 * @param array<object> $data    List of objects for this page.
	 * @param int           $page    Page requested (0 = first).
	 * @param int           $count   Number of items returned in this page.
	 * @param int|null      $total   Total number of rows across all pages, if known.
	 * @param bool          $hasMore Whether there is at least one more page after this one.
	 */
	public function __construct($data, int $page, int $count, ?int $total = null, bool $hasMore = false) {
		$this->data = $data;
		$this->page = $page;
		$this->count = $count;
		$this->total = $total;
		$this->hasMore = $hasMore;
	}
}
