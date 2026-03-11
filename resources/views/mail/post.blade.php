<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7;">
        <tr>
            <td align="center" style="padding: 24px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; max-width: 600px; width: 100%;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 32px 40px 24px; border-bottom: 1px solid #e8e8eb;">
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1a1a2e; line-height: 1.3;">
                                {{ $post->title }}
                            </h1>
                            <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                {{ $post->date->format('F j, Y') }}
                                @if($post->category)
                                    &middot; {{ $post->category }}
                                @endif
                            </p>
                        </td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px 40px; font-size: 16px; line-height: 1.6; color: #374151;">
                            {!! $post->html !!}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
