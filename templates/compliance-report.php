<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCI DSS Compliance Report - <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
        }
        .report-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .report-header {
            text-align: center;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .report-title {
            font-size: 28px;
            margin: 0;
            color: #0073aa;
        }
        .report-subtitle {
            font-size: 16px;
            margin: 10px 0 0 0;
            color: #666;
        }
        .report-meta {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        .report-meta table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-meta td {
            padding: 5px 0;
            border: none;
        }
        .report-meta .label {
            font-weight: bold;
            width: 150px;
        }
        .score-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f0f8ff;
            border-radius: 8px;
            border: 1px solid #0073aa;
        }
        .score-display {
            font-size: 48px;
            font-weight: bold;
            color: #0073aa;
            margin: 10px 0;
        }
        .score-status {
            font-size: 18px;
            margin: 10px 0;
        }
        .score-high { color: #00a32a; }
        .score-medium { color: #dba617; }
        .score-low { color: #d63638; }
        .requirements-section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 20px;
            margin: 30px 0 15px 0;
            color: #0073aa;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .requirement-pass {
            background: #d5e7d5;
            border-color: #00a32a;
        }
        .requirement-fail {
            background: #f7d5d5;
            border-color: #d63638;
        }
        .requirement-manual {
            background: #fff8e5;
            border-color: #dba617;
        }
        .requirement-title {
            font-weight: bold;
            flex: 1;
        }
        .requirement-status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #fff;
        }
        .status-pass { color: #00a32a; }
        .status-fail { color: #d63638; }
        .status-manual { color: #dba617; }
        .recommendations {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .recommendations h3 {
            margin-top: 0;
            color: #d63638;
        }
        .recommendations ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .recommendations li {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body { background: #fff; }
            .report-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h1 class="report-title">PCI DSS Compliance Assessment Report</h1>
            <p class="report-subtitle"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
        </div>

        <div class="report-meta">
            <table>
                <tr>
                    <td class="label">Report Generated:</td>
                    <td><?php echo esc_html( current_time( 'F j, Y g:i A' ) ); ?></td>
                </tr>
                <tr>
                    <td class="label">Website URL:</td>
                    <td><?php echo esc_html( home_url() ); ?></td>
                </tr>
                <tr>
                    <td class="label">Assessment Version:</td>
                    <td>PCI DSS v3.2.1</td>
                </tr>
                <tr>
                    <td class="label">Plugin Version:</td>
                    <td>SkyLearn Billing Pro <?php echo esc_html( SLBP_VERSION ); ?></td>
                </tr>
            </table>
        </div>

        <div class="score-section">
            <h2>Overall Compliance Score</h2>
            <div class="score-display <?php echo esc_attr( $assessment['score'] >= 80 ? 'score-high' : ( $assessment['score'] >= 60 ? 'score-medium' : 'score-low' ) ); ?>">
                <?php echo esc_html( $assessment['score'] ); ?>/100
            </div>
            <div class="score-status">
                <?php if ( $assessment['score'] >= 80 ) : ?>
                    <span class="score-high">Excellent Compliance</span>
                <?php elseif ( $assessment['score'] >= 60 ) : ?>
                    <span class="score-medium">Good Compliance</span>
                <?php else : ?>
                    <span class="score-low">Needs Improvement</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="requirements-section">
            <h2 class="section-title">PCI DSS Requirements Assessment</h2>
            
            <?php foreach ( $assessment['requirements'] as $key => $requirement ) : ?>
            <div class="requirement-item requirement-<?php echo esc_attr( str_replace( '_', '-', $requirement['status'] ) ); ?>">
                <div class="requirement-title"><?php echo esc_html( $requirement['title'] ); ?></div>
                <div class="requirement-status status-<?php echo esc_attr( str_replace( '_', '-', $requirement['status'] ) ); ?>">
                    <?php echo esc_html( strtoupper( str_replace( '_', ' ', $requirement['status'] ) ) ); ?>
                </div>
            </div>
            <p style="margin: 5px 0 15px 15px; color: #666; font-size: 14px;">
                <?php echo esc_html( $requirement['description'] ); ?>
            </p>
            <?php endforeach; ?>
        </div>

        <?php if ( ! empty( $assessment['recommendations'] ) ) : ?>
        <div class="recommendations">
            <h3>Security Recommendations</h3>
            <p>The following recommendations should be implemented to improve your PCI DSS compliance:</p>
            <ul>
                <?php foreach ( $assessment['recommendations'] as $recommendation ) : ?>
                <li><?php echo esc_html( $recommendation ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="section-title">Detailed Requirement Breakdown</div>
        
        <h3>Build and Maintain a Secure Network and Systems</h3>
        <ul>
            <li><strong>Requirement 1:</strong> Install and maintain a firewall configuration to protect cardholder data</li>
            <li><strong>Requirement 2:</strong> Do not use vendor-supplied defaults for system passwords and other security parameters</li>
        </ul>

        <h3>Protect Cardholder Data</h3>
        <ul>
            <li><strong>Requirement 3:</strong> Protect stored cardholder data</li>
            <li><strong>Requirement 4:</strong> Encrypt transmission of cardholder data across open, public networks</li>
        </ul>

        <h3>Maintain a Vulnerability Management Program</h3>
        <ul>
            <li><strong>Requirement 5:</strong> Protect all systems against malware and regularly update anti-virus software or programs</li>
            <li><strong>Requirement 6:</strong> Develop and maintain secure systems and applications</li>
        </ul>

        <h3>Implement Strong Access Control Measures</h3>
        <ul>
            <li><strong>Requirement 7:</strong> Restrict access to cardholder data by business need to know</li>
            <li><strong>Requirement 8:</strong> Identify and authenticate access to system components</li>
            <li><strong>Requirement 9:</strong> Restrict physical access to cardholder data</li>
        </ul>

        <h3>Regularly Monitor and Test Networks</h3>
        <ul>
            <li><strong>Requirement 10:</strong> Track and monitor all access to network resources and cardholder data</li>
            <li><strong>Requirement 11:</strong> Regularly test security systems and processes</li>
        </ul>

        <h3>Maintain an Information Security Policy</h3>
        <ul>
            <li><strong>Requirement 12:</strong> Maintain a policy that addresses information security for all personnel</li>
        </ul>

        <div class="footer">
            <p><strong>Disclaimer:</strong> This report is generated by an automated assessment tool and should not be considered a substitute for a professional PCI DSS audit. For official compliance certification, please consult with a Qualified Security Assessor (QSA).</p>
            <p>Generated by SkyLearn Billing Pro Security Module</p>
        </div>
    </div>
</body>
</html>