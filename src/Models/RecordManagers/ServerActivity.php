<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\RecordManagers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerActivity extends \Hubleto\App\Community\Calendar\Models\RecordManagers\Activity
{
  public $table = 'servers_activities';

  /** @return BelongsTo<Server, covariant ServerActivity> */
  public function SERVER(): BelongsTo
  {
    return $this->belongsTo(Server::class, 'id_server', 'id');
  }

  /** @return BelongsTo<ActivityTemplate, covariant ServerActivity> */
  public function TEMPLATE(): BelongsTo
  {
    return $this->belongsTo(ActivityTemplate::class, 'id_template', 'id');
  }

  /** @return HasMany<ActivityReport, covariant ServerActivity> */
  public function REPORTS(): HasMany
  {
    return $this->hasMany(ActivityReport::class, 'id_activity', 'id');
  }

  /**
   * Prepares the query for reading records.
   *
   * @param mixed $query
   * @param int $level
   * @param array|null $includeRelations
   *
   * @return mixed
   *
   */
  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);
    return $query;
  }

  /**
   * Adds filters given in the URL to the query.
   *
   * @param mixed $query
   *
   * @return mixed
   *
   */
  public function addUrlFiltersToQuery(mixed $query): mixed
  {
    $query = parent::addUrlFiltersToQuery($query);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    $filters = $hubleto->router()->urlParamAsArray('filters');

    if ($hubleto->router()->urlParamAsInteger('idServer') > 0) {
      $query = $query->where($this->table . '.id_server', $hubleto->router()->urlParamAsInteger('idServer'));
    }

    if ($hubleto->router()->urlParamAsInteger('idTemplate') > 0) {
      $query = $query->where($this->table . '.id_template', $hubleto->router()->urlParamAsInteger('idTemplate'));
    }

    $valuesWhen = array_map('intval', (array) ($filters['fActivityWhen'] ?? []));
    if (!empty($valuesWhen)) {
      $table = $this->table;
      $today = date('Y-m-d');
      $query = $query->where(function ($q) use ($table, $today, $valuesWhen) {
        foreach ($valuesWhen as $when) {
          if ($when === 1) {
            $q->orWhere(function ($q2) use ($table, $today) {
              $q2->where($table . '.completed', 0)->where($table . '.date_start', '<', $today)
                ->where(function ($q3) use ($table) { $q3->whereNull($table . '.recurrence')->orWhere($table . '.recurrence', ''); });
            });
          } elseif ($when === 2) {
            $q->orWhere(function ($q2) use ($table, $today) { $q2->where($table . '.completed', 0)->where($table . '.date_start', $today); });
          } elseif ($when === 3) {
            $q->orWhere(function ($q2) use ($table, $today) { $q2->where($table . '.completed', 0)->where($table . '.date_start', '>', $today); });
          } elseif ($when === 4) {
            $q->orWhere($table . '.completed', 1);
          }
        }
      });
    }

    return $query;
  }

  /**
   * Prepares data for lookup inputs.
   *
   * @param array $dataRaw
   *
   * @return array
   *
   */
  public function prepareLookupData(array $dataRaw): array
  {
    $data = parent::prepareLookupData($dataRaw);
    return $data;
  }

}
