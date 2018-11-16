<?php

namespace AppBundle\Entity;

use AppBundle\Action\Task\Done as TaskDone;
use AppBundle\Action\Task\Failed as TaskFailed;
use AppBundle\Action\Task\UnassignedTasks;
use AppBundle\Entity\Task\Group as TaskGroup;
use AppBundle\Entity\Model\TaggableInterface;
use AppBundle\Entity\Model\TaggableTrait;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *   attributes={
 *     "denormalization_context"={"groups"={"task"}},
 *     "normalization_context"={"groups"={"task", "delivery", "place"}}
 *   },
 *   collectionOperations={
 *     "get"={"method"="GET"},
 *     "my_tasks" = {
 *       "route_name" = "my_tasks",
 *       "swagger_context" = {
 *         "parameters" = {{
 *           "name" = "date",
 *           "in" = "path",
 *           "required" = "true",
 *           "type" = "string"
 *         }}
 *       }
 *     },
 *     "unassigned_tasks"={
 *       "method"="GET",
 *       "path"="/tasks/{date}/unassigned",
 *       "controller"=UnassignedTasks::class,
 *       "access_control"="is_granted('ROLE_ADMIN')"
 *     }
 *   },
 *   itemOperations={
 *     "get"={"method"="GET"},
 *     "task_done"={
 *       "method"="PUT",
 *       "path"="/tasks/{id}/done",
 *       "controller"=TaskDone::class,
 *       "access_control"="is_granted('ROLE_COURIER') and object.isAssignedTo(user)"
 *     },
 *     "task_failed"={
 *       "method"="PUT",
 *       "path"="/tasks/{id}/failed",
 *       "controller"=TaskFailed::class,
 *       "access_control"="is_granted('ROLE_COURIER') and object.isAssignedTo(user)"
 *     }
 *   }
 * )
 */
class Task implements TaggableInterface
{
    use TaggableTrait;

    const TYPE_DROPOFF = 'DROPOFF';
    const TYPE_PICKUP = 'PICKUP';

    const STATUS_TODO = 'TODO';
    const STATUS_FAILED = 'FAILED';
    const STATUS_DONE = 'DONE';
    const STATUS_CANCELLED = 'CANCELLED';

    /**
     * @Groups({"task"})
     */
    private $id;

    /**
     * @Groups({"task"})
     */
    private $type = self::TYPE_DROPOFF;

    /**
     * @Groups({"task"})
     */
    private $status = self::STATUS_TODO;

    private $delivery;

    /**
     * @Groups({"task"})
     */
    private $address;

    /**
     * @Groups({"task"})
     */
    private $doneAfter;

    /**
     * @Assert\NotBlank()
     * @Groups({"task"})
     */
    private $doneBefore;

    /**
     * @Groups({"task"})
     */
    private $comments;

    /**
     * @Groups({"task"})
     */
    private $events;

    /**
     * @Groups({"task"})
     */
    private $createdAt;

    /**
     * @Groups({"task"})
     */
    private $updatedAt;

    private $previous;

    private $next;

    private $group;

    /**
     * @Groups({"task"})
     */
    private $assignedTo;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDelivery()
    {
        return $this->delivery;
    }

    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function isPickup()
    {
        return $this->type === self::TYPE_PICKUP;
    }

    public function isDropoff()
    {
        return $this->type === self::TYPE_DROPOFF;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function isDone()
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isFinished()
    {
        return $this->isDone() || $this->isFailed();
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    public function getDoneAfter()
    {
        return $this->doneAfter;
    }

    public function setDoneAfter(\DateTime $doneAfter = null)
    {
        $this->doneAfter = $doneAfter;

        return $this;
    }

    public function getDoneBefore()
    {
        return $this->doneBefore;
    }

    public function setDoneBefore(\DateTime $doneBefore = null)
    {
        $this->doneBefore = $doneBefore;

        return $this;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function getPrevious()
    {
        return $this->previous;
    }

    public function setPrevious(Task $previous = null)
    {
        $this->previous = $previous;

        return $this;
    }

    public function hasPrevious()
    {
        return $this->previous !== null;
    }

    public function getNext()
    {
        return $this->next;
    }

    public function setNext(Task $next = null)
    {
        $this->next = $next;

        return $this;
    }

    public function hasNext()
    {
        return $this->next !== null;
    }

    public function isAssigned()
    {
        return null !== $this->assignedTo;
    }

    public function isAssignedTo(ApiUser $courier)
    {
        return $this->isAssigned() && $this->assignedTo === $courier;
    }

    public function getAssignedCourier()
    {
        return $this->assignedTo;
    }

    public function assignTo(ApiUser $courier)
    {
        $this->assignedTo = $courier;
    }

    public function unassign()
    {
        $this->assignedTo = null;
    }

    public function hasEvent($name)
    {
        foreach ($this->getEvents() as $event) {
            if ($event->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    public function getLastEvent($name)
    {
        $criteria = Criteria::create()->orderBy(array("created_at" => Criteria::DESC));

        foreach ($this->getEvents()->matching($criteria) as $event) {
            if ($event->getName() === $name) {
                return $event;
            }
        }
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup(TaskGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }
}
