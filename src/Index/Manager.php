<?php

namespace Sigma\Index;

use Sigma\Element;
use Sigma\Collection;
use Sigma\Common\Bootable;
use Sigma\Index\Action\Get as GetAction;
use Sigma\Index\Action\Insert as InsertAction;
use Sigma\Index\Action\Remove as RemoveAction;
use Sigma\Index\Action\Listing as ListingAction;
use Sigma\Index\Response\Get as GetResponse;
use Sigma\Index\Response\Insert as InsertResponse;
use Sigma\Index\Response\Remove as RemoveResponse;
use Sigma\Index\Response\Listing as ListingResponse;
use Sigma\Contract\Bootable as BootableInterface;

class Manager implements BootableInterface
{
    use Bootable;

    /**
     * Dispatch the index insert action and
     * pass the response to the handler
     *
     * @param Element $index
     *
     * @return boolean
     */
    public function insert(Element $index): Element
    {
        return $this->execute($index, new InsertAction, new InsertResponse);
    }

    /**
     * Dispatch the index remove action and
     * pass the response to the handler
     *
     * @param string $identifier
     *
     * @return boolean
     */
    public function remove(string $identifier): bool
    {
        return $this->execute($identifier, new RemoveAction, new RemoveResponse);
    }

    /**
     * Dispatch the index listing action and
     * pass the response to the handler
     *
     * @param string $name
     *
     * @return Collection
     */
    public function list(string $name = '*'): Collection
    {
        return $this->execute($name, new ListingAction, new ListingResponse);
    }

    /**
     * Dispatch the index get action and
     * pass the response to the handler
     *
     * @param string $name
     *
     * @return Element
     */
    public function get(string $name): Element
    {
        return $this->execute($name, new GetAction, new GetResponse);
    }
}
