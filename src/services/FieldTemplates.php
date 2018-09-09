<?php
namespace fruitstudios\palette\services;

use fruitstudios\palette\Palette;
use fruitstudios\palette\models\FieldTemplate;
use fruitstudios\palette\records\FieldTemplate as FieldTemplateRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;

class FieldTemplates extends Component
{

    // Properties
    // =========================================================================

    private $_fetchedAllFieldTemplates = false;
    private $_allFieldTemplates = [];
    private $_allFieldTemplatesByType = [];

    // Public Methods
    // =========================================================================

    public function getAllFieldTemplates(): array
    {
        if($this->_fetchedAllFieldTemplates)
        {
            return $this->_allFieldTemplates;
        }

        $results = $this->_createFieldTemplatesQuery()->all();
        foreach($results as $result)
        {
            $fieldTemplate = $this->_populateFieldTemplate($result);
            $this->_allFieldTemplates[$result['id']] = $fieldTemplate;
            $this->_allFieldTemplatesByType[$result['type']] = $fieldTemplate;
        }
        $this->_fetchedAllFieldTemplates = true;
        return $this->_allFieldTemplates;
    }

    public function getAllFieldTemplatesByType(string $type): array
    {
        if(!$this->_fetchedAllFieldTemplates)
        {
            $this->getAllFieldTemplates();
        }
        return $this->_allFieldTemplatesByType[$type] ?? [];
    }

    public function getFieldTemplateById($id)
    {
        if(isset($this->_allFieldTemplates[$id]))
        {
            return $this->_allFieldTemplates[$id];
        }

        if ($this->_fetchedAllFieldTemplates)
        {
            return null;
        }

        $result = $this->_createFieldTemplatesQuery()
            ->where(['id' => $id])
            ->one();

        if (!$result)
        {
            return null;
        }
        return $this->_allFieldTemplates[$id] = $this->_populateFieldTemplate($result);
    }

    public function saveFieldTemplate(FieldTemplate $model, bool $runValidation = true): bool
    {
        if ($model->id)
        {
            $record = FieldTemplateRecord::findOne($model->id);
            if (!$record)
            {
                throw new Exception(Craft::t('palette', 'No field template exists with the ID “{id}”', ['id' => $model->id]));
            }
        }
        else
        {
            $record = new FieldTemplateRecord();
        }

        if ($runValidation && !$model->validate())
        {
            Craft::info('Field template not saved due to validation error.', __METHOD__);
            return false;
        }

        $fields = [
            'name',
            'type',
            'settings'
        ];

        foreach ($fields as $field)
        {
            $record->$field = $model->$field;
        }

        // Save it!
        $record->save(false);

        // Now that we have a record ID, save it on the model
        $model->id = $record->id;

        return true;
    }

    public function deleteFieldTemplateById($id): bool
    {
        $record = FieldTemplateRecord::findOne($id);
        if ($record)
        {
            return (bool)$record->delete();
        }
        return false;
    }

    // Private Methods
    // =========================================================================

    private function _createFieldTemplatesQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'type',
                'settings',
            ])
            ->from(['{{%palette_fieldtemplates}}']);
    }


    private function _populateFieldTemplate(array $result): FieldTemplate
    {
        return new FieldTemplate($result);
    }

}