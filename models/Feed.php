<?php namespace VojtaSvoboda\ShopaholicFeeds\Models;

use Backend\Facades\BackendAuth;
use Backend\Models\User as BackendUser;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use Config;
use Lovata\Shopaholic\Models\Currency;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation as ValidationTrait;
use October\Rain\Exception\ApplicationException;
use Str;

/**
 * Feed Model
 */
class Feed extends Model
{
    use ValidationTrait;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'vojtasvoboda_shopaholicfeeds_feeds';

    /**
     * @var array Validation rules for attributes.
     */
    public $rules = [
        'name' => 'max:300',
        'slug' => 'required|max:191|unique:vojtasvoboda_shopaholicfeeds_feeds',
        'hash' => 'required|size:16|unique:vojtasvoboda_shopaholicfeeds_feeds',
        'format' => 'required',
        'only_private' => 'boolean',
        'log_enabled' => 'boolean',
        'enabled' => 'boolean',
    ];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances.
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['ip_addresses'];

    /**
     * @var array $hasMany Relations.
     */
    public $hasMany = [
        'logs' => [Log::class],
    ];

    /**
     * @var array $belongsTo Relations.
     */
    public $belongsTo = [
        'author' => [
            BackendUser::class,
            'key' => 'created_by',
        ],
        'editor' => [
            BackendUser::class,
            'key' => 'updated_by',
        ],
        'currency' => [
            Currency::class,
        ],
    ];

    /**
     * Before validate event handler.
     */
    public function beforeValidate()
    {
        if (empty($this->hash)) {
            $this->hash = Str::random(16);
        }
    }

    /**
     * Before create event handler.
     */
    public function beforeCreate()
    {
        $backend_user = BackendAuth::getUser();
        if (!empty($backend_user)) {
            $this->created_by = $backend_user->id;
            $this->updated_by = $backend_user->id;
        }
    }

    /**
     * Before update event handler.
     */
    public function beforeUpdate()
    {
        $backend_user = BackendAuth::getUser();
        if (!empty($backend_user)) {
            $this->updated_by = $backend_user->id;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getFormatOptions()
    {
        $builders = Config::get('vojtasvoboda.shopaholicfeeds::config.builders', []);
        $list = [];
        foreach ($builders as $key => $builder) {
            $list[$key] = $builder['name'];
        }

        return $list;
    }

    /**
     * @return array<string, string>
     * @throws ApplicationException
     */
    public function getProductPageOptions()
    {
        if (!$theme = Theme::getEditTheme()) {
            throw new ApplicationException('Unable to find the active theme.');
        }

        return Page::listInTheme($theme)->lists('fileName', 'fileName');
    }

    /**
     * @param string $ip_addr
     * @return bool
     */
    public function isAllowed($ip_addr)
    {
        if (empty($this->ip_addresses)) {
            return true;
        }

        foreach ($this->ip_addresses as $ip_address) {
            if ($ip_addr === trim($ip_address['ip_addr'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsEnabled(Builder $query)
    {
        return $query->where('enabled', true);
    }

    /**
     * @param Builder $query
     * @param string $slug
     * @return Builder
     */
    public function scopeSearchBySlug(Builder $query, $slug)
    {
        return $query->where(function (Builder $query) use ($slug) {
            // get slug last part, which could be hash
            $parts = explode('-', $slug);
            $lastPart = end($parts);
            array_pop($parts);
            $withoutLastPart = implode('-', $parts);

            // feed could be public, so check both public and private URLs
            $query->where(function (Builder $query) use ($slug, $lastPart, $withoutLastPart) {
                $query
                    ->where('only_private', false)
                    ->where(function (Builder $query) use ($slug, $lastPart, $withoutLastPart) {
                        // where (slug = withoutLastPart and hash = lastPart) or slug = slug
                        $query->where(function (Builder $query) use ($lastPart, $withoutLastPart) {
                            $query->where('slug', $withoutLastPart)->where('hash', $lastPart);
                        })->orWhere('slug', $slug);
                    });

            // or could be private available only with private URL
            })->orWhere(function (Builder $query) use ($slug, $lastPart, $withoutLastPart) {
                $query
                    ->where('only_private', true)
                    ->where('slug', $withoutLastPart)->where('hash', $lastPart);
            });
        });
    }
}
