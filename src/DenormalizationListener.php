<?php

namespace Fabstract\Component\REST;

use Fabs\Component\Event\ListenerInterface;
use Fabs\Component\Http\Exception\StatusCodeException\UnprocessableEntityException;
use Fabs\Component\Http\Injectable;
use Fabs\Component\LINQ\LINQ;
use Fabs\Component\Serializer\Event\DenormalizationFinishedEvent;
use Fabs\Component\Validator\ValidatableInterface;
use Fabstract\Component\REST\Model\ValidationErrorModel;

class DenormalizationListener extends Injectable implements ListenerInterface, \Fabstract\Component\REST\Injectable
{
    /**
     * @param DenormalizationFinishedEvent $event
     * @return void
     * @throws UnprocessableEntityException
     */
    public function onEvent($event)
    {
        if ($event->getDepth() !== 0) {
            // Validations should start running from depth 0
            return;
        }

        $normalized = $event->getDenormalizedObject();
        if ($normalized instanceof ValidatableInterface) {
            $validation_error_list = $this->validator->validate($normalized);
            if (count($validation_error_list) > 0) {
                $validation_error_model_list = LINQ::from($validation_error_list)
                    ->select(function ($validation_error) {
                        /** @var \Fabs\Component\Validator\ValidationError $validation_error */
                        return ValidationErrorModel::create($validation_error);
                    })
                    ->toArray();

                throw new UnprocessableEntityException($validation_error_model_list);
            }
        }
    }

    /**
     * @return string
     */
    public
    function getChannel()
    {
        return DenormalizationFinishedEvent::class;
    }
}
