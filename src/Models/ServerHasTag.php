<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\Framework\Db\Column\Lookup;

class ServerHasTag extends \Hubleto\Erp\Model
{
  public string $table = 'servers_has_tags';
  public string $recordManagerClass = RecordManagers\ServerHasTag::class;
  public ?string $lookupSqlValue = 'concat("#", {%TABLE%}.id)';

  public array $relations = [
    'SERVER' => [ self::BELONGS_TO, Server::class, 'id_server', 'id' ],
    'TAG' => [ self::BELONGS_TO, Tag::class, 'id_tag', 'id' ],
  ];

  /**
   * Describes columns of the model.
   *
   * @return array
   *
   */
  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_server' => (new Lookup($this, $this->translate('Server'), Server::class))->setFkOnDelete('CASCADE')->setRequired()->setDefaultVisible(),
      'id_tag' => (new Lookup($this, $this->translate('Tag'), Tag::class))->setFkOnDelete('CASCADE')->setRequired()->setDefaultVisible(),
    ]);
  }

  /**
   * Describes the table (list) of records.
   *
   * @return \Hubleto\Framework\Description\Table
   *
   */
  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();

    $description->ui['addButtonText'] = $this->translate('Add tag assignment');

    $description->ui['showHeader'] = true;
    $description->ui['showFooter'] = false;

    return $description;
  }

}
