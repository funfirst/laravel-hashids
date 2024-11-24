<?php

namespace FF\LaravelHashids\Traits;

use FF\LaravelHashids\Scopes\HashidScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Vinkla\Hashids\Facades\Hashids;

/**
 * @method Model|null findByHashid($hashid)
 * @method Model findByHashidOrFail($hashid)
 */
trait HasHashidRouting 
{

     /**
     * @see parent
     */
    public function resolveRouteBinding($query, $value, $field = null)
    {
        if (($field && $field === $this->getHashColumnName()) || is_numeric($value)) {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->byHash($value);
    }

	/**
	 * @see parent
	 */
	public function getRouteKey()
	{
		return $this->hashid();
	}

	/**
	 * @see parent
	 */
	public function getRouteKeyName()
	{
		return null;
	}

}