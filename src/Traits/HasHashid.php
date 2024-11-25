<?php

namespace FF\LaravelHashids\Traits;

use FF\LaravelHashids\Repository;
use FF\LaravelHashids\Scopes\HashidScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use LogicException;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
/**
 * @method Model|null findByHashid($hashid)
 * @method Model findByHashidOrFail($hashid)
 */
trait HasHashid 
{

    public function getPrefix(): string
    {
        $prefix = property_exists($this, 'idPrefix')
            ? $this->idPrefix
            : Str::snake(class_basename($this));

        $ts = $this->created_at ? base_convert($this->created_at->timestamp * 10000000, 10, 36) : '';

        return $prefix . '_' . $ts;
    }

    public function objectId()
	{
		return $this->getPrefix() . self::idToHash($this->getKey());
	}

    public static function objectIdToId(string $objectId): int
    {
        $hash = substr($objectId, -config('hashids.hash_length'));
        return self::hashToId($hash);
    }

    /**
     * Get Model by hash.
     *
     * @param $hash
     *
     * @return self|null
     */
    public static function byObjectId($objectId): ?self
    {
        return self::query()->byObjectId($objectId)->first();
    }

    public function scopeByObjectId(Builder $query, string $objectId): Builder
    {
        return  $this->shouldHashPersist()
            ? $query->where($this->qualifyColumn($this->getHashColumnName()), $objectId)
            : $query->where($this->getQualifiedKeyName(), self::objectIdToId($objectId));
    }

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

    public function hashid()
	{
		return self::idToHash($this->getKey());
	}

    /**
     * Get Model by hash.
     *
     * @param $hash
     *
     * @return self|null
     */
    public static function byHash($hash): ?self
    {
        return self::query()->byHash($hash)->first();
    }

    /**
     * Get model by hash or fail.
     *
     * @param $hash
     *
     * @return self
     *
     * @throw \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function byHashOrFail($hash): self
    {
        return self::query()->byHash($hash)->firstOrFail();
    }

    /**
     * Get Hash Attribute.
     *
     * @return string|null
     */
    public function getHashAttribute(): ?string
    {
        return $this->exists
            ? $this->getHashIdRepository()->idToHash($this->getKey(), $this->getHashKey())
            : null;
    }

    /**
     * Decode Hash to ID for the model.
     *
     * @param string $hash
     *
     * @return int|null
     */
    public static function hashToId(string $hash): ?int
    {
        return (new static())
           ->getHashIdRepository()
           ->hashToId($hash, (new static())->getHashKey());
    }

    /**
     * Get Hash Key.
     *
     * @return string
     */
    public function getHashKey(): string
    {
        return property_exists($this, 'hashKey')
            ? $this->hashKey
            : static::class;
    }

    /**
     * Encode Id to Hash for the model.
     *
     * @param int $primaryKey
     *
     * @return string
     */
    public static function idToHash(int $primaryKey): string
    {
        return (new static())
            ->getHashIdRepository()
            ->idToHash($primaryKey, (new static())->getHashKey());
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

    /**
     * Get HashId column name.
     *
     * @return string
     */
    public function getHashColumnName(): string
    {
        return property_exists($this, 'hashColumnName')
            ? $this->hashColumnName
            : 'hashid';
    }

    /**
     * register boot trait method.
     *
     * @return void
     */
    public static function bootHasHashid()
    {
        self::created(function ($model) {
            if ($model->shouldHashPersist()) {
                $model->{$model->getHashColumnName()} = self::idToHash($model->getKey());

                $model->save();
            }
        });
    }

    /**
     * Get HashId Repository.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return \FF\LaravelHashids\Repository
     */
    protected function getHashIdRepository(): Repository
    {
        if ($this->getKeyType() !== 'int') {
            throw new LogicException('Invalid implementation of HashId, only works with `int` value of `keyType`');
        }

        // get custom salt for the model (if exists)
        if (method_exists($this, 'getHashIdSalt')) {
            // force the repository to make a new instance of hashid.
            app('app.hashid')->make($this->getHashKey(), $this->getHashIdSalt());
        }

        return app('app.hashid');
    }

}