<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gapura Training Notification</title>
    <style>
        /* Reset styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .email-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }

        .logo {
            display: inline-block;
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .system-name {
            font-size: 14px;
            opacity: 0.9;
        }

        .email-body {
            padding: 40px;
        }

        .notification-content {
            background-color: #f8fafc;
            border-left: 4px solid #10B981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }

        .urgent-notification {
            border-left-color: #EF4444;
            background-color: #FEF2F2;
        }

        .warning-notification {
            border-left-color: #F59E0B;
            background-color: #FFFBEB;
        }

        .info-notification {
            border-left-color: #3B82F6;
            background-color: #EFF6FF;
        }

        .content-text {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
            white-space: pre-line;
        }

        .action-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #10B981;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: background-color 0.3s ease;
        }

        .action-button:hover {
            background-color: #059669;
        }

        .urgent-button {
            background-color: #EF4444;
        }

        .urgent-button:hover {
            background-color: #DC2626;
        }

        .warning-button {
            background-color: #F59E0B;
        }

        .warning-button:hover {
            background-color: #D97706;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .details-table th,
        .details-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .details-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-expired {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .status-expiring {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .email-footer {
            background-color: #f9fafb;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .footer-links {
            margin: 15px 0;
        }

        .footer-links a {
            color: #10B981;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .disclaimer {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        /* Responsive design */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 0;
            }

            .email-header,
            .email-body,
            .email-footer {
                padding: 20px;
            }

            .details-table {
                font-size: 14px;
            }

            .details-table th,
            .details-table td {
                padding: 8px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #1f2937;
            }

            .email-body {
                color: #f9fafb;
            }

            .notification-content {
                background-color: #374151;
                color: #f9fafb;
            }

            .details-table {
                background-color: #374151;
            }

            .details-table th {
                background-color: #4b5563;
                color: #f9fafb;
            }

            .details-table td {
                color: #f9fafb;
                border-color: #4b5563;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="logo">G</div>
            <div class="company-name">GAPURA</div>
            <div class="system-name">Training Management System</div>
        </div>

        <!-- Body -->
        <div class="email-body">
            <!-- Dynamic content based on notification type -->
            @if(isset($priority))
                @if($priority === 'urgent')
                    <div class="notification-content urgent-notification">
                @elseif($priority === 'high')
                    <div class="notification-content warning-notification">
                @elseif($priority === 'low')
                    <div class="notification-content info-notification">
                @else
                    <div class="notification-content">
                @endif
            @else
                <div class="notification-content">
            @endif
                <div class="content-text">{!! nl2br(e($content)) !!}</div>
            </div>

            <!-- Action button if URL provided -->
            @if(isset($actionUrl) && $actionUrl)
                <div style="text-align: center; margin: 30px 0;">
                    @if(isset($priority) && $priority === 'urgent')
                        <a href="{{ $actionUrl }}" class="action-button urgent-button">
                    @elseif(isset($priority) && $priority === 'high')
                        <a href="{{ $actionUrl }}" class="action-button warning-button">
                    @else
                        <a href="{{ $actionUrl }}" class="action-button">
                    @endif
                        {{ $actionText ?? 'View Details' }}
                    </a>
                </div>
            @endif

            <!-- Certificate details table if provided -->
            @if(isset($certificateDetails) && $certificateDetails)
                <table class="details-table">
                    <thead>
                        <tr>
                            <th colspan="2">Certificate Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($certificateDetails['certificate_number']))
                            <tr>
                                <td><strong>Certificate Number</strong></td>
                                <td>{{ $certificateDetails['certificate_number'] }}</td>
                            </tr>
                        @endif
                        @if(isset($certificateDetails['training_name']))
                            <tr>
                                <td><strong>Training Type</strong></td>
                                <td>{{ $certificateDetails['training_name'] }}</td>
                            </tr>
                        @endif
                        @if(isset($certificateDetails['issue_date']))
                            <tr>
                                <td><strong>Issue Date</strong></td>
                                <td>{{ $certificateDetails['issue_date'] }}</td>
                            </tr>
                        @endif
                        @if(isset($certificateDetails['expiry_date']))
                            <tr>
                                <td><strong>Expiry Date</strong></td>
                                <td>{{ $certificateDetails['expiry_date'] }}</td>
                            </tr>
                        @endif
                        @if(isset($certificateDetails['status']))
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>
                                    @if($certificateDetails['status'] === 'active')
                                        <span class="status-badge status-active">Active</span>
                                    @elseif($certificateDetails['status'] === 'expired')
                                        <span class="status-badge status-expired">Expired</span>
                                    @else
                                        <span class="status-badge status-expiring">{{ ucfirst($certificateDetails['status']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        @if(isset($certificateDetails['verification_url']))
                            <tr>
                                <td><strong>Verification</strong></td>
                                <td><a href="{{ $certificateDetails['verification_url'] }}" style="color: #10B981;">Verify Certificate</a></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            @endif

            <!-- Employee details if provided -->
            @if(isset($employeeDetails) && $employeeDetails)
                <table class="details-table">
                    <thead>
                        <tr>
                            <th colspan="2">Employee Information</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($employeeDetails['name']))
                            <tr>
                                <td><strong>Name</strong></td>
                                <td>{{ $employeeDetails['name'] }}</td>
                            </tr>
                        @endif
                        @if(isset($employeeDetails['employee_id']))
                            <tr>
                                <td><strong>Employee ID</strong></td>
                                <td>{{ $employeeDetails['employee_id'] }}</td>
                            </tr>
                        @endif
                        @if(isset($employeeDetails['department']))
                            <tr>
                                <td><strong>Department</strong></td>
                                <td>{{ $employeeDetails['department'] }}</td>
                            </tr>
                        @endif
                        @if(isset($employeeDetails['position']))
                            <tr>
                                <td><strong>Position</strong></td>
                                <td>{{ $employeeDetails['position'] }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <div class="footer-text">
                <strong>Gapura Training Management System</strong>
            </div>
            <div class="footer-text">
                Soekarno-Hatta International Airport, Terminal 2F
            </div>

            <div class="footer-links">
                <a href="{{ config('app.url') }}">Training Portal</a>
                <a href="{{ config('app.url') }}/certificates">My Certificates</a>
                <a href="mailto:training@gapura.com">Support</a>
            </div>

            <div class="disclaimer">
                <p><strong>Important:</strong> This is an automated notification from the Gapura Training Management System. Please do not reply to this email.</p>
                <p>If you have questions about your training or certificates, please contact the HR Training Department at training@gapura.com or call +62-21-5550123.</p>
                <p><strong>Privacy Notice:</strong> This email contains confidential employee information. If you received this in error, please delete it immediately and notify training@gapura.com.</p>
                <p>© {{ date('Y') }} PT. Gapura Angkasa. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
