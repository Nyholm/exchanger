<?php

declare(strict_types=1);

/*
 * This file is part of Exchanger.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exchanger\Service;

use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\ExchangeRate;
use Exchanger\HistoricalExchangeRateQuery;
use Exchanger\StringUtil;
use Exchanger\Exception\UnsupportedCurrencyPairException;
use Exchanger\Contract\ExchangeRate as ExchangeRateContract;

/**
 * CurrencyDataFeed Service.
 */
final class CurrencyDataFeed extends Service
{
    const URL = 'https://currencydatafeed.com/api/data.php?token=%s&currency=%s';

    const HISTORICAL_URL = 'https://currencydatafeed.com/api/historical.php?token=%s&date=%s&currency=%s';

    /**
     * {@inheritdoc}
     */
    public function processOptions(array &$options): void
    {
        if (!isset($options['api_key'])) {
            throw new \InvalidArgumentException('The "api_key" option must be provided.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportQuery(ExchangeRateQuery $exchangeQuery): bool
    {
        return !$exchangeQuery instanceof HistoricalExchangeRateQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(ExchangeRateQuery $exchangeRateQuery): ExchangeRateContract
    {
        $currencyPair = $exchangeRateQuery->getCurrencyPair();
        $url = sprintf(self::URL, $this->options['api_key'], $currencyPair->getBaseCurrency().'/'.$currencyPair->getQuoteCurrency());

        $content = $this->request($url);

        $data = StringUtil::jsonToArray($content);

        if (!empty($data) && $data['status'] && !isset($data['currency'][0]['error'])) {
            $date = (new \DateTime())->setTimestamp(strtotime($data['currency'][0]['date']));

            return new ExchangeRate((float) ($data['currency'][0]['value']), __CLASS__, $date);
        }

        throw new UnsupportedCurrencyPairException($currencyPair, $this);
    }
}
