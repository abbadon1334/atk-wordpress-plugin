<?php

declare(strict_types=1);

namespace Atk4\AtkWordpress\Interfaces;

interface IMetaboxField
{
    /**
     * Initialised Field use in meta box using a MetaFieldInterface $ctrl.
     * This function is called early in class constructor that implement this interface.
     * The ctrl pass as an argument to this function must also implement the MetaFieldInterface.
     * MetaBoxes field needs to be defined early prior to added to your layout.
     *
     * Using ctrl pass as argument, field object instance are added to a container.
     *  - ex: $ctrl->addField('test', new \Atk4\Ui\FormField\Line(), '_atksample_test');
     *
     * When time is need to add field in your layout, in atk init method for example,
     * then the field object instance retrieve from the container is added to the layout.
     *  - ex: $this->add($this->fieldCtrl->getField('test'));
     */
    public function onInitMetaBoxFields(IMetaField $ctrl);

    /**
     * Give a chance to update raw data prior to save it to database.
     * For example, you could escape $data from <script> character using strip_tags function.
     * This method is called automatically for each fields in container and should return field value.
     *
     * public function onUpdateMetaFieldRawData($fieldName, $data)
     * {
     *      //this will remove strip_tags on all field, no matter the field name.
     *      return strip_tags($data);
     * }
     *
     * @return mixed //string
     */
    public function onUpdateMetaFieldRawData($fieldName, $data);
}
