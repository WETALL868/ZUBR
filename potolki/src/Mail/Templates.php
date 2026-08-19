<?php

declare(strict_types=1);

namespace Potolki\Mail;

/**
 * Шаблоны писем.
 *
 * Письмо владельцу — рабочий документ: все поля заявки, расчёт построчно,
 * версии согласий и техническая информация для разбора спорных случаев.
 *
 * Письмо клиенту — транзакционное: подтверждение заявки и её содержимое.
 * Рекламы в нём нет: она допустима только при отдельном согласии
 * и отправляется отдельными письмами.
 */
final class Templates
{
    // ── Письмо владельцу ──────────────────────────────────────────────────

    public function ownerHtml(array $lead): string
    {
        $rows = [
            'Заявка'          => $lead['id'] . ' от ' . $lead['created_at'],
            'Форма'           => $lead['form_title'],
            'Имя'             => $lead['name'] ?? '—',
            'Телефон'         => $lead['phone_display'] ?? '—',
            'Email'           => $lead['email'] ?? '—',
            'Удобная связь'   => $this->contactWay($lead['contact_way'] ?? 'phone'),
            'Город / территория' => $lead['city'] ?? '—',
            'Страница'        => $lead['page'],
        ];

        if (!empty($lead['comment'])) {
            $rows['Комментарий'] = nl2br(e($lead['comment']));
        }

        $html = $this->head('Заявка № ' . e($lead['id']));
        $html .= $this->table($rows);

        if (!empty($lead['estimate'])) {
            $html .= $this->estimateHtml($lead['estimate']);
        }

        $source = [];
        foreach ((array) ($lead['utm'] ?? []) as $key => $value) {
            $source[$key] = (string) $value;
        }
        if (!empty($lead['referrer'])) {
            $source['referrer'] = $lead['referrer'];
        }
        if ($source !== []) {
            $html .= '<h3 style="' . $this->h3Style() . '">Источник</h3>' . $this->table($source);
        }

        $consents = $lead['consents'];
        $html .= '<h3 style="' . $this->h3Style() . '">Согласия</h3>';
        $html .= $this->table([
            'Персональные данные' => $this->consentLine($consents['personal_data']),
            'Реклама'             => $this->consentLine($consents['advertising']),
            'Отправлено'          => $consents['technical']['submitted_at'],
            'Браузер'             => e((string) $consents['technical']['user_agent']),
            'Идентификатор отправителя' => $consents['technical']['ip_hash'],
        ]);

        $html .= '<p style="' . $this->noteStyle() . '">Версия прайса: ' . e($lead['price_version'])
            . '. Расчёт предварительный, окончательная цена — после замера.</p>';

        return $html . $this->foot();
    }

    public function ownerText(array $lead): string
    {
        $lines = [
            'ЗАЯВКА № ' . $lead['id'] . ' от ' . $lead['created_at'],
            'Форма: ' . $lead['form_title'],
            'Имя: ' . ($lead['name'] ?? '—'),
            'Телефон: ' . ($lead['phone_display'] ?? '—'),
            'Email: ' . ($lead['email'] ?? '—'),
            'Удобная связь: ' . $this->contactWay($lead['contact_way'] ?? 'phone'),
            'Город: ' . ($lead['city'] ?? '—'),
            'Страница: ' . $lead['page'],
        ];

        if (!empty($lead['comment'])) {
            $lines[] = 'Комментарий: ' . $lead['comment'];
        }

        if (!empty($lead['estimate'])) {
            $lines[] = '';
            $lines[] = $this->estimateText($lead['estimate']);
        }

        if (!empty($lead['utm'])) {
            $lines[] = '';
            $lines[] = 'Источник:';
            foreach ((array) $lead['utm'] as $key => $value) {
                $lines[] = '  ' . $key . ': ' . $value;
            }
        }
        if (!empty($lead['referrer'])) {
            $lines[] = '  referrer: ' . $lead['referrer'];
        }

        $consents = $lead['consents'];
        $lines[] = '';
        $lines[] = 'Согласия:';
        $lines[] = '  Персональные данные: ' . strip_tags($this->consentLine($consents['personal_data']));
        $lines[] = '  Реклама: ' . strip_tags($this->consentLine($consents['advertising']));
        $lines[] = '  Отправлено: ' . $consents['technical']['submitted_at'];
        $lines[] = '  Идентификатор отправителя: ' . $consents['technical']['ip_hash'];
        $lines[] = '';
        $lines[] = 'Версия прайса: ' . $lead['price_version'];

        return implode("\n", $lines);
    }

    // ── Письмо клиенту ────────────────────────────────────────────────────

    public function clientHtml(array $lead): string
    {
        $brand = (string) config('brand.name');
        $phone = (string) config('contacts.phone.display');
        $email = (string) config('contacts.email');

        $html = $this->head('Заявка № ' . e($lead['id']) . ' принята');

        $html .= '<p style="' . $this->pStyle() . '">Здравствуйте' . (!empty($lead['name']) ? ', ' . e($lead['name']) : '')
            . '! Мы получили вашу заявку и свяжемся с вами в рабочее время.</p>';

        $rows = [
            'Номер заявки' => $lead['id'],
            'Дата'         => $lead['created_at'],
            'Тип обращения'=> $lead['form_title'],
        ];
        if (!empty($lead['city'])) {
            $rows['Территория'] = $lead['city'];
        }
        $html .= $this->table($rows);

        if (!empty($lead['estimate'])) {
            $html .= $this->estimateHtml($lead['estimate']);
            $html .= '<p style="' . $this->noteStyle() . '">Это предварительный расчёт по указанным вами параметрам. '
                . 'Он не является публичной офертой: окончательная стоимость определяется после замера '
                . 'и фиксируется в смете и договоре.</p>';
        }

        $html .= '<h3 style="' . $this->h3Style() . '">Как с нами связаться</h3>';
        $html .= '<p style="' . $this->pStyle() . '">' . e($brand) . '<br>'
            . 'Телефон: ' . e($phone) . '<br>'
            . 'Почта: ' . e($email) . '</p>';

        $html .= '<p style="' . $this->noteStyle() . '">Письмо отправлено по вашей заявке на сайте. '
            . 'Как мы обрабатываем данные и как отозвать согласие — в политике: '
            . '<a href="' . e(absolute_url((string) config('consent.privacy_policy.url'))) . '">'
            . e(absolute_url((string) config('consent.privacy_policy.url'))) . '</a></p>';

        return $html . $this->foot();
    }

    public function clientText(array $lead): string
    {
        $lines = [
            'Здравствуйте' . (!empty($lead['name']) ? ', ' . $lead['name'] : '') . '!',
            '',
            'Мы получили вашу заявку № ' . $lead['id'] . ' от ' . $lead['created_at'] . ' и свяжемся с вами в рабочее время.',
        ];

        if (!empty($lead['estimate'])) {
            $lines[] = '';
            $lines[] = $this->estimateText($lead['estimate']);
            $lines[] = '';
            $lines[] = 'Это предварительный расчёт. Он не является публичной офертой: окончательная стоимость определяется после замера и фиксируется в смете и договоре.';
        }

        $lines[] = '';
        $lines[] = (string) config('brand.name');
        $lines[] = 'Телефон: ' . config('contacts.phone.display');
        $lines[] = 'Почта: ' . config('contacts.email');
        $lines[] = '';
        $lines[] = 'Как мы обрабатываем данные и как отозвать согласие: ' . absolute_url((string) config('consent.privacy_policy.url'));

        return implode("\n", $lines);
    }

    // ── Смета ─────────────────────────────────────────────────────────────

    private function estimateHtml(array $estimate): string
    {
        $html = '<h3 style="' . $this->h3Style() . '">Предварительный расчёт '
            . (filled($estimate['quote_number'] ?? null) ? '№ ' . e((string) $estimate['quote_number']) : '')
            . '</h3>';

        foreach ($estimate['rooms'] as $room) {
            $html .= '<p style="' . $this->pStyle() . '"><strong>' . e($room['title']) . '</strong> — '
                . e((string) $room['area']) . ' м², периметр ' . e((string) $room['perimeter']) . ' пог. м, '
                . e($room['canvas']['title']) . ', ' . e($room['mounting']['title']) . '</p>';

            $html .= '<table style="width:100%;border-collapse:collapse;font-size:14px;margin:0 0 14px;">';
            foreach ($room['lines'] as $line) {
                $html .= '<tr>'
                    . '<td style="padding:6px 8px;border-bottom:1px solid #E6E2DA;">' . e($line['title']) . '</td>'
                    . '<td style="padding:6px 8px;border-bottom:1px solid #E6E2DA;white-space:nowrap;">'
                    . e((string) $line['qty']) . ' ' . e($line['unit']) . ' × ' . money((float) $line['rate']) . '</td>'
                    . '<td style="padding:6px 8px;border-bottom:1px solid #E6E2DA;text-align:right;white-space:nowrap;">'
                    . money((float) $line['sum']) . '</td>'
                    . '</tr>';
            }
            $html .= '<tr><td colspan="2" style="padding:6px 8px;"><strong>Итого по помещению</strong></td>'
                . '<td style="padding:6px 8px;text-align:right;"><strong>' . money((float) $room['subtotal']) . '</strong></td></tr>';
            $html .= '</table>';
        }

        $totals = $estimate['totals'];
        $rows = [];

        if (!empty($estimate['travel']['sum'])) {
            $rows['Выезд (' . $estimate['travel']['billable_km'] . ' км сверх бесплатной зоны)'] = money((float) $estimate['travel']['sum']);
        }
        $rows['Сумма'] = money((float) $totals['subtotal']);
        if ((float) $totals['discount'] > 0) {
            $rows['Скидка ' . $totals['discount_percent'] . ' %'] = '−' . money((float) $totals['discount']);
        }
        if ($totals['minimum_applied']) {
            $rows['Минимальная сумма заказа'] = money((float) $totals['minimum_order']);
        }
        $rows['Итого'] = money((float) $totals['total']);

        $html .= $this->table($rows);

        if (!empty($estimate['warnings'])) {
            $html .= '<p style="' . $this->noteStyle() . '">' . implode('<br>', array_map('e', $estimate['warnings'])) . '</p>';
        }

        return $html;
    }

    private function estimateText(array $estimate): string
    {
        $lines = ['ПРЕДВАРИТЕЛЬНЫЙ РАСЧЁТ'
            . (filled($estimate['quote_number'] ?? null) ? ' № ' . $estimate['quote_number'] : '')];

        foreach ($estimate['rooms'] as $room) {
            $lines[] = '';
            $lines[] = sprintf(
                '%s — %s м², периметр %s пог. м, %s, %s',
                $room['title'],
                $room['area'],
                $room['perimeter'],
                $room['canvas']['title'],
                $room['mounting']['title']
            );
            foreach ($room['lines'] as $line) {
                $lines[] = sprintf(
                    '  %s: %s %s × %s = %s',
                    $line['title'],
                    $line['qty'],
                    $line['unit'],
                    money((float) $line['rate']),
                    money((float) $line['sum'])
                );
            }
            $lines[] = '  Итого по помещению: ' . money((float) $room['subtotal']);
        }

        $totals = $estimate['totals'];
        $lines[] = '';
        if (!empty($estimate['travel']['sum'])) {
            $lines[] = 'Выезд: ' . money((float) $estimate['travel']['sum']);
        }
        $lines[] = 'Сумма: ' . money((float) $totals['subtotal']);
        if ((float) $totals['discount'] > 0) {
            $lines[] = 'Скидка: −' . money((float) $totals['discount']);
        }
        if ($totals['minimum_applied']) {
            $lines[] = 'Применена минимальная сумма заказа: ' . money((float) $totals['minimum_order']);
        }
        $lines[] = 'ИТОГО: ' . money((float) $totals['total']);

        foreach ((array) ($estimate['warnings'] ?? []) as $warning) {
            $lines[] = '! ' . $warning;
        }

        return implode("\n", $lines);
    }

    // ── Оформление ────────────────────────────────────────────────────────

    private function head(string $title): string
    {
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $title . '</title></head>'
            . '<body style="margin:0;padding:24px;background:#F5F2EC;font-family:Arial,Helvetica,sans-serif;color:#171A1D;">'
            . '<div style="max-width:640px;margin:0 auto;background:#FFFFFF;border:1px solid #E2DCD2;border-radius:12px;padding:28px;">'
            . '<h2 style="margin:0 0 18px;font-size:20px;line-height:1.3;color:#171A1D;">' . $title . '</h2>';
    }

    private function foot(): string
    {
        return '</div></body></html>';
    }

    private function table(array $rows): string
    {
        $html = '<table style="width:100%;border-collapse:collapse;font-size:14px;margin:0 0 18px;">';
        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                . '<td style="padding:7px 8px;border-bottom:1px solid #E6E2DA;color:#5C6268;width:42%;">' . $label . '</td>'
                . '<td style="padding:7px 8px;border-bottom:1px solid #E6E2DA;">' . $value . '</td>'
                . '</tr>';
        }
        return $html . '</table>';
    }

    private function consentLine(array $consent): string
    {
        return ($consent['given'] ? 'да' : 'нет')
            . ', версия ' . $consent['version'] . ' от ' . $consent['date']
            . ($consent['given_at'] ? ', получено ' . $consent['given_at'] : '');
    }

    private function contactWay(string $key): string
    {
        return [
            'phone'    => 'звонок',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'email'    => 'email',
        ][$key] ?? 'звонок';
    }

    private function pStyle(): string
    {
        return 'margin:0 0 14px;font-size:15px;line-height:1.6;color:#171A1D;';
    }

    private function h3Style(): string
    {
        return 'margin:22px 0 10px;font-size:16px;line-height:1.3;color:#171A1D;';
    }

    private function noteStyle(): string
    {
        return 'margin:14px 0 0;font-size:13px;line-height:1.55;color:#5C6268;';
    }
}
