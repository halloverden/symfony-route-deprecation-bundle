<?php

namespace HalloVerden\RouteDeprecationBundle\Helper;

final readonly class DateTimeHelper {
  const FORMAT_DATE_ONLY = 'Y-m-d';
  const FORMAT_DATE_ONLY_WITH_TIMEZONE = 'Y-m-dP';

  private function __construct() {
  }

  /**
   * @param string $dateString
   *
   * @return \DateTimeInterface
   * @throws \Exception
   */
  public static function getDateTimeFromString(string $dateString): \DateTimeInterface {
    $utcTimezone = new \DateTimeZone('UTC');
    $dateTime = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $dateString);

    if (false !== $dateTime) {
      return $dateTime->setTimezone($utcTimezone);
    }

    $dateTime = \DateTimeImmutable::createFromFormat(self::FORMAT_DATE_ONLY, $dateString, $utcTimezone);

    if (false !== $dateTime) {
      return $dateTime->setTime(0, 0);
    }

    $dateTime = \DateTimeImmutable::createFromFormat(self::FORMAT_DATE_ONLY_WITH_TIMEZONE, $dateString);

    if (false !== $dateTime) {
      return $dateTime->setTime(0, 0)->setTimezone($utcTimezone);
    }

    throw new \Exception(\sprintf('Invalid date date format (%s)', $dateString));
  }

}
