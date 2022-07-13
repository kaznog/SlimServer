<?php
namespace App\Models;

/**
 * ページング用オブジェクト
 */
class Pager
{
    public $path;
    public $limit;
    public $page;
    public $count;

    /**
     * コンストラクタ.
     * @param string $path  ベースパス
     * @param int    $limit 1ページあたりの要素数.
     * @param int    $count 全要素数.
     * @param int    $page  現在のページ(0から始まる値).
     */
    public function __construct($path, $limit, $count = 0, $page = 0)
    {
        $this->path = $path;
        $this->limit = $limit;
        $this->count = $count;
        $this->page = $page;
    }

    /**
     * 前のページがあるか.
     * @return bool
     */
    public function hasPrev()
    {
        return ($this->page > 0);
    }

    /**
     * 次のページがあるか.
     * @return bool
     */
    public function hasNext()
    {
        $maxPage = floor(($this->count - 1) / $this->limit);

        return ($this->page < $maxPage);
    }

    /**
     * 現在のページの最初の要素のインデックス.
     * @return int
     */
    public function from()
    {
        return $this->limit * $this->page + 1;
    }

    /**
     * 現在のページの最後の要素のインデックス.
     * @return int
     */
    public function to()
    {
        return min($this->count, $this->limit * ($this->page + 1));
    }

    /**
     * 前のページのパス.
     * @return string
     */
    public function prevPath()
    {
        $prevPage = $this->page - 1;
        if (strpos($this->path, '?') !== false) {
            return "{$this->path}&page={$prevPage}";
        } else {
            return "{$this->path}?page={$prevPage}";
        }
    }

    /**
     * 次のページのパス.
     * @return string
     */
    public function nextPath()
    {
        $nextPage = $this->page + 1;
        if (strpos($this->path, '?') !== false) {
            return "{$this->path}&page={$nextPage}";
        } else {
            return "{$this->path}?page={$nextPage}";
        }
    }

    /**
     * 現在のパス.
     * @return string
     */
    public function getPath()
    {
        if (strpos($this->path, '?') !== false) {
            return "{$this->path}&page=";
        } else {
            return "{$this->path}?page=";
        }
    }

}
