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
     * Get Model by hashed key.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $hash
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByHash(Builder $query, string $hash): Builder
    {
        return  $this->shouldHashPersist()
            ? $query->where($this->qualifyColumn($this->getHashColumnName()), $hash)
            : $query->where($this->getQualifiedKeyName(), self::hashToId($hash));
    }

    /**
     * Determine if hash should persist in database.
     *
     * @return bool
     */
    public function shouldHashPersist(): bool
    {
        return property_exists($this, 'shouldHashPersist')
            ? $this->shouldHashPersist
            : false;
    }










	public static function bootHasHashid()
	{
		static::addGlobalScope(new HashidScope);
	}

	public function hashid()
	{
		return $this->idToHashid($this->getKey());
	}

	/**
	 * Decode the hashid to the id
	 *
	 * @param string $hashid
	 * @return int|null
	 */
	public function hashidToId($hashid)
	{
		return @Hashids::connection($this->getHashidsConnection())
			->decode($hashid)[0];
	}

	/**
	 * Encode an id to its equivalent hashid
	 *
	 * @param string $id
	 * @return string|null
	 */
	public function idToHashid($id)
	{
		return @Hashids::connection($this->getHashidsConnection())
			->encode($id);
	}

	public function getHashidsConnection()
	{
		return config('hashids.default');
	}

	protected function getHashidAttribute()
    {
        return $this->hashid();
    }

}