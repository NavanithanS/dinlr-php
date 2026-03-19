<?php
namespace Nava\Dinlr\Tests;

use Nava\Dinlr\Client;
use Nava\Dinlr\Exception\ApiException;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    protected $testConfig;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        echo "\n\n✅ TEST CASE: Reservations API";
        echo "\n==============================================================";
        echo "\nSetting up test configuration...";

        $this->testConfig = require __DIR__ . '/config.php';
        $this->client     = new Client($this->testConfig);

        echo "\n• API URL: " . $this->testConfig['api_url'];
        echo "\n• Restaurant ID: " . $this->testConfig['restaurant_id'];
        echo "\n--------------------------------------------------------------";
    }

    public function testGetExperiences()
    {
        echo "\n\nSTEP 1: Testing get experiences";
        echo "\n--------------------------------------------------------------";

        try {
            $locations = $this->client->locations()->list();
            if (count($locations) === 0) {
                $this->markTestSkipped('No locations available');
                return;
            }

            $locationId  = $locations->first()->getId();
            $experiences = $this->client->experiences()->list($locationId);

            echo "\n• Total experiences: " . count($experiences);

            if (count($experiences) > 0) {
                $experience = $experiences->first();
                echo "\n• First experience ID: " . $experience->getId();
                echo "\n• Name: " . $experience->getName();
                echo "\n• Sort order: " . $experience->getSort();
            }

            echo "\n✓ Experiences retrieved successfully";

            $this->assertInstanceOf(\Nava\Dinlr\Models\ExperienceCollection::class, $experiences);

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Experiences not available: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testGetTableSections()
    {
        echo "\n\nSTEP 2: Testing get table sections";
        echo "\n--------------------------------------------------------------";

        try {
            $locations = $this->client->locations()->list();
            if (count($locations) === 0) {
                $this->markTestSkipped('No locations available');
                return;
            }

            $locationId    = $locations->first()->getId();
            $tableSections = $this->client->tableSections()->list($locationId);

            echo "\n• Total table sections: " . count($tableSections);

            if (count($tableSections) > 0) {
                $section = $tableSections->first();
                echo "\n• First section ID: " . $section->getId();
                echo "\n• Name: " . $section->getName();
            }

            echo "\n✓ Table sections retrieved successfully";

            $this->assertInstanceOf(\Nava\Dinlr\Models\TableSectionCollection::class, $tableSections);

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Table sections not available: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testGetAvailableServices()
    {
        echo "\n\nSTEP 3: Testing get available reservation times";
        echo "\n--------------------------------------------------------------";

        try {
            $locations = $this->client->locations()->list();
            if (count($locations) === 0) {
                $this->markTestSkipped('No locations available');
                return;
            }

            $locationId = $locations->first()->getId();
            $date       = (new \DateTime('+1 day'))->format('Y-m-d');
            $adult      = 2;
            $children   = 0;

            echo "\n• Checking availability for: " . $date;
            echo "\n• Adults: " . $adult;
            echo "\n• Children: " . $children;

            $services = $this->client->reservations()->getAvailableServices($locationId, $date, $adult, $children);

            echo "\n• Available services: " . count($services);

            if (count($services) > 0) {
                $service = $services->first();
                echo "\n• First service: " . $service->getName();
                echo "\n• Available times: " . count($service->getAvailableTimes());

                if (count($service->getAvailableTimes()) > 0) {
                    echo "\n• First available time: " . $service->getAvailableTimes()[0]['time'];
                }
            }

            echo "\n✓ Available services retrieved successfully";

            $this->assertInstanceOf(\Nava\Dinlr\Models\ServiceCollection::class, $services);

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Service availability not available: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testListReservations()
    {
        echo "\n\nSTEP 4: Testing list reservations";
        echo "\n--------------------------------------------------------------";

        try {
            $reservations = $this->client->reservations()->list();

            echo "\n• Total reservations: " . count($reservations);

            if (count($reservations) > 0) {
                $reservation = $reservations->first();
                echo "\n• First reservation ID: " . $reservation->getId();
                echo "\n• Reservation number: " . $reservation->getReservationNumber();
                echo "\n• Time: " . $reservation->getReservationTime();
                echo "\n• Pax: " . $reservation->getPax();
                echo "\n• Status: " . $reservation->getStatus();
                echo "\n• Deposit: " . $reservation->getTotalDeposit();
            }

            // Test collection methods
            $booked = $reservations->getByStatus('booked');
            echo "\n• Booked reservations: " . count($booked);

            $upcoming = $reservations->getUpcoming();
            echo "\n• Upcoming reservations: " . count($upcoming);

            echo "\n✓ Reservations retrieved successfully";

            $this->assertInstanceOf(\Nava\Dinlr\Models\ReservationCollection::class, $reservations);

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Reservations not available: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testBookReservation()
    {
        echo "\n\nSTEP 5: Testing book reservation";
        echo "\n--------------------------------------------------------------";

        try {
            $locations = $this->client->locations()->list();
            if (count($locations) === 0) {
                $this->markTestSkipped('No locations available');
                return;
            }

            $locationId = $locations->first()->getId();
            $date       = (new \DateTime('+1 week'))->format('Y-m-d');

            // Get available services first
            $services = $this->client->reservations()->getAvailableServices($locationId, $date, 2, 0);

            if (count($services) === 0 || count($services->getAvailable()) === 0) {
                $this->markTestSkipped('No available services for booking');
                return;
            }

            $service        = $services->getAvailable()[0];
            $availableTimes = $service->getAvailableTimes();

            if (empty($availableTimes)) {
                $this->markTestSkipped('No available times for booking');
                return;
            }

            $reservationData = [
                'location'         => $locationId,
                'reservation_info' => [
                    'reservation_no'   => 'TEST' . time(),
                    'reservation_time' => $availableTimes[0]['time'],
                    'service'          => $service->getId(),
                    'first_name'       => 'Test',
                    'last_name'        => 'Reservation',
                    'email'            => 'test@example.com',
                    'phone'            => '+1234567890',
                    'pax'              => 2,
                    'adult'            => 2,
                    'children'         => 0,
                    'notes'            => 'Test reservation from API test',
                ],
            ];

            echo "\n• Booking test reservation";
            echo "\n• Service: " . $service->getName();
            echo "\n• Time: " . $availableTimes[0]['time'];

            $reservation = $this->client->reservations()->book($reservationData);

            echo "\n• Reservation booked successfully";
            echo "\n• Reservation ID: " . $reservation->getId();
            echo "\n• Reservation number: " . $reservation->getReservationNumber();
            echo "\n• Status: " . $reservation->getStatus();

            echo "\n✓ Reservation booking successful";

            $this->assertInstanceOf(\Nava\Dinlr\Models\Reservation::class, $reservation);
            $this->assertNotEmpty($reservation->getId());

            return $reservation;

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Reservation booking not available: ' . $e->getMessage());
            } else {
                $this->fail('Reservation booking failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * @depends testBookReservation
     */
    public function testChangeReservationStatus($reservation)
    {
        echo "\n\nSTEP 6: Testing change reservation status";
        echo "\n--------------------------------------------------------------";

        try {
            if (!$reservation || !$reservation->getId()) {
                $this->markTestSkipped('No reservation passed from testBookReservation');
                return;
            }

            echo "\n• Changing status for reservation ID: " . $reservation->getId();

            // Test cancellation
            $updatedReservation = $this->client->reservations()->cancel($reservation->getId());
            
            echo "\n• New Status: " . $updatedReservation->getStatus();
            echo "\n✓ Reservation status changed successfully";

            $this->assertEquals('cancelled', $updatedReservation->getStatus());

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Reservation status change not available: ' . $e->getMessage());
            } else {
                $this->fail('Reservation status change failed: ' . $e->getMessage());
            }
        }
    }
}
