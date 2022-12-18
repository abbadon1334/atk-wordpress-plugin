<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Components;

use Atk4\AtkWordpress\Controllers\ComponentController;
use Atk4\AtkWordpress\Interfaces\IMetaboxField;
use Atk4\AtkWordpress\Interfaces\IMetaField;

abstract class Metabox extends AbstractComponent implements IMetaboxField
{
    /**
     * The controller need to add input field in metabox.
     */
    public ?IMetaField $fieldCtrl = null;

    /**
     * The arguments set in config-metabox file.
     */
    public ?array $args = null;

    /**
     * MetaBoxComponent constructor.
     *
     * Note: You can override this constructor in your plugin file in order to setup your
     * own MetaField controller.
     *
     * @param null $label
     * @param null $class
     */
    public function __construct($label = null, $class = null, IMetaField $fieldCtrl = null)
    {
        parent::__construct($label, $class);

        $this->fieldCtrl = $fieldCtrl;

        if (!$this->fieldCtrl instanceof \Atk4\AtkWordpress\Interfaces\IMetaField) {
            $this->fieldCtrl = new MetaFieldController();
        }

        $this->onInitMetaBoxFields($this->fieldCtrl);
    }

    public function setFieldInput($postId, ComponentController $compCtrl): void
    {
        if ($this->fieldCtrl !== null) {
            foreach ($this->fieldCtrl->getFields() as $field) {
                $field->set($compCtrl->getPostMetaData($postId, $field->short_name, true));
            }
        }
    }

    public function savePost(int $postId, ComponentController $compCtrl): void
    {
        if ($this->fieldCtrl !== null) {
            foreach ($this->fieldCtrl->getFields() as $key => $field) {
                if (isset($_POST) && array_key_exists($field->short_name, $_POST)) {
                    $compCtrl->savePostMetaData(
                        $postId,
                        $field->short_name,
                        $this->onUpdateMetaFieldRawData($key, $_POST[$field->short_name])
                    );
                }
            }
        }
    }
}
