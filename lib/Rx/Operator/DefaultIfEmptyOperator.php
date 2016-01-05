<?php

namespace Rx\Operator;

use Rx\Disposable\SerialDisposable;
use Rx\ObservableInterface;
use Rx\Observer\CallbackObserver;
use Rx\ObserverInterface;
use Rx\SchedulerInterface;

class DefaultIfEmptyOperator implements OperatorInterface
{
    /** @var  ObservableInterface */
    private $observable;

    /** @var bool */
    private $passThrough = false;

    public function __construct(ObservableInterface $observable)
    {
        $this->observable = $observable;
    }

    /**
     * @param \Rx\ObservableInterface $observable
     * @param \Rx\ObserverInterface $observer
     * @param \Rx\SchedulerInterface $scheduler
     * @return \Rx\DisposableInterface
     */
    public function __invoke(ObservableInterface $observable, ObserverInterface $observer, SchedulerInterface $scheduler = null)
    {
        $disposable = new SerialDisposable();
        $disposable->setDisposable($observable->subscribe(new CallbackObserver(
            function ($x) use ($observer) {
                $this->passThrough = true;
                $observer->onNext($x);

            },
            function ($e) use ($observer) {
                $observer->onError($e);
            },
            function () use ($observer, $disposable) {
                if (!$this->passThrough) {
                    $disposable->setDisposable($this->observable->subscribe($observer));
                    return;
                }

                $observer->onCompleted();
            }
        ), $scheduler));

        return $disposable;
    }
}
