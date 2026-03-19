<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\Reservation as ReservationModel;
use Nava\Dinlr\Models\ReservationCollection;
use Nava\Dinlr\Models\ServiceCollection;

class Reservation extends AbstractResource
{
    protected $resourcePath = 'onlineorder/reservations';

    public function getAvailableServices(string $locationId, string $date, int $adult, int $children = 0, string $restaurantId = null): ServiceCollection
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new ValidationException('Date must be in YYYY-MM-DD format');
        }

        $path   = $this->buildPath($restaurantId) . '/../services';
        $params = [
            'location_id' => $locationId,
            'date'        => $date,
            'adult'       => $adult,
            'children'    => $children,
        ];

        $response = $this->client->request('GET', $path, $params);

        return new ServiceCollection($response['data'] ?? []);
    }

    public function book(array $reservationData, string $restaurantId = null): ReservationModel
    {
        $this->validateRequired($reservationData, ['location', 'reservation_info']);

        $info = $reservationData['reservation_info'];
        $this->validateRequired($info, ['reservation_time', 'service', 'pax', 'adult', 'children']);

        $path     = $this->buildPath($restaurantId);
        $response = $this->client->request('POST', $path, $reservationData);

        return new ReservationModel($response['data'] ?? []);
    }

    public function list(string $restaurantId = null, array $params = []): ReservationCollection
    {
        $path     = $this->buildPath($restaurantId);
        $response = $this->client->request('GET', $path, $params);

        return new ReservationCollection($response['data'] ?? []);
    }

    public function get(string $reservationId, string $restaurantId = null): ReservationModel
    {
        $path     = $this->buildPath($restaurantId, $reservationId);
        $response = $this->client->request('GET', $path);

        return new ReservationModel($response['data'] ?? []);
    }

    public function update(string $reservationId, array $data, string $restaurantId = null): ReservationModel
    {
        $path     = $this->buildPath($restaurantId, $reservationId);
        $response = $this->client->request('PUT', $path, $data);

        return new ReservationModel($response['data'] ?? []);
    }

    public function setBooked(string $reservationId, string $restaurantId = null): ReservationModel
    {
        return $this->updateStatus($reservationId, 'book', $restaurantId);
    }

    public function setArrived(string $reservationId, string $restaurantId = null): ReservationModel
    {
        return $this->updateStatus($reservationId, 'arrive', $restaurantId);
    }

    public function setSeated(string $reservationId, string $restaurantId = null): ReservationModel
    {
        return $this->updateStatus($reservationId, 'seat', $restaurantId);
    }

    public function setCompleted(string $reservationId, string $restaurantId = null): ReservationModel
    {
        return $this->updateStatus($reservationId, 'complete', $restaurantId);
    }

    public function setNoShow(string $reservationId, string $restaurantId = null): ReservationModel
    {
        return $this->updateStatus($reservationId, 'no_show', $restaurantId);
    }

    public function cancel(string $reservationId, string $restaurantId = null): ReservationModel
    {
        return $this->updateStatus($reservationId, 'cancel', $restaurantId);
    }

    public function setPendingPayment(string $reservationId, string $restaurantId = null): ReservationModel
    {
        return $this->updateStatus($reservationId, 'pending_payment', $restaurantId);
    }

    /**
     * Helper method to update a reservation's status
     *
     * @param string $reservationId
     * @param string $statusAction The endpoint action (e.g. 'book', 'arrive', 'cancel')
     * @param string|null $restaurantId
     * @return ReservationModel
     */
    protected function updateStatus(string $reservationId, string $statusAction, string $restaurantId = null): ReservationModel
    {
        $this->validateString($reservationId, 'Reservation ID');

        $path     = $this->buildPath($restaurantId, "{$reservationId}/{$statusAction}");
        $response = $this->client->request('POST', $path);

        return new ReservationModel($response['data'] ?? []);
    }
}
