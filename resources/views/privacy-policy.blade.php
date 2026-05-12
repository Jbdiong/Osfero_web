<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Osfero</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f9fafb;
            color: #111827;
        }
    </style>
</head>
<body class="antialiased">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8 bg-white shadow-sm my-8 rounded-xl border border-gray-100">
        <div class="prose prose-slate max-w-none">
            <div class="mb-6">
                <a href="{{ route('filament.admin.auth.login') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                    <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Login
                </a>
            </div>
            
            <h1 class="text-4xl font-bold mb-4 text-gray-900">Privacy Policy</h1>
            <p class="text-sm text-gray-500 mb-8">Last updated: {{ date('F d, Y') }}</p>

            <section class="mb-8">
                <p class="mb-4">
                    Osfero ("we", "our", or "us") operates the Osfero website and the Osfero mobile application (the "Service"). 
                    This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.
                </p>
                <p class="mb-4">
                    We use your data to provide and improve the Service. By using the Service, you agree to the collection and use of information in accordance with this policy.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">1. Information Collection and Use</h2>
                <p class="mb-4">We collect several different types of information for various purposes to provide and improve our Service to you.</p>
                
                <h3 class="text-xl font-medium mb-2 text-gray-700">Types of Data Collected</h3>
                <h4 class="text-lg font-medium mb-1 text-gray-700">Personal Data</h4>
                <p class="mb-4">
                    While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you ("Personal Data"). Personally identifiable information may include, but is not limited to:
                </p>
                <ul class="list-disc pl-6 mb-4 space-y-1">
                    <li>Email address</li>
                    <li>First name and last name</li>
                    <li>Phone number</li>
                    <li>Address, State, Province, ZIP/Postal code, City</li>
                    <li>Cookies and Usage Data</li>
                </ul>

                <h4 class="text-lg font-medium mb-1 text-gray-700">Usage Data & Push Notifications</h4>
                <p class="mb-4">
                    We may also collect information that your browser sends whenever you visit our Service or when you access the Service by or through a mobile device ("Usage Data").
                    This Usage Data may include information such as your computer's Internet Protocol address (e.g. IP address), browser type, browser version, the pages of our Service that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers and other diagnostic data.
                </p>
                <p class="mb-4">
                    When you access the Service by or through a mobile device, this Usage Data may include information such as the type of mobile device you use, your mobile device unique ID, the IP address of your mobile device, your mobile operating system, the type of mobile Internet browser you use, unique device identifiers (including Firebase Cloud Messaging tokens for push notifications), and other diagnostic data.
                </p>

                <h4 class="text-lg font-medium mb-1 text-gray-700">Client and CRM Data</h4>
                <p class="mb-4">
                    As a Customer Relationship Management (CRM) platform, you may input data about your own clients, leads, events, and business operations into Osfero. In this context, you act as the "Data Controller" for this information, and Osfero acts as the "Data Processor." We only host and process this data securely to provide the Service to you and do not use your CRM data for our own marketing purposes.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">2. Use of Data</h2>
                <p class="mb-4">Osfero uses the collected data for various purposes:</p>
                <ul class="list-disc pl-6 mb-4 space-y-1">
                    <li>To provide and maintain the Service</li>
                    <li>To notify you about changes to our Service</li>
                    <li>To allow you to participate in interactive features of our Service when you choose to do so</li>
                    <li>To provide customer care and support</li>
                    <li>To provide analysis or valuable information so that we can improve the Service</li>
                    <li>To monitor the usage of the Service</li>
                    <li>To detect, prevent and address technical issues</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">3. Transfer of Data</h2>
                <p class="mb-4">
                    Your information, including Personal Data, may be transferred to — and maintained on — computers located outside of your state, province, country or other governmental jurisdiction where the data protection laws may differ than those from your jurisdiction.
                </p>
                <p class="mb-4">
                    Your consent to this Privacy Policy followed by your submission of such information represents your agreement to that transfer.
                </p>
                <p class="mb-4">
                    Osfero will take all steps reasonably necessary to ensure that your data is treated securely and in accordance with this Privacy Policy and no transfer of your Personal Data will take place to an organization or a country unless there are adequate controls in place including the security of your data and other personal information.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">4. Disclosure of Data</h2>
                <p class="mb-4">Osfero may disclose your Personal Data in the good faith belief that such action is necessary to:</p>
                <ul class="list-disc pl-6 mb-4 space-y-1">
                    <li>To comply with a legal obligation</li>
                    <li>To protect and defend the rights or property of Osfero</li>
                    <li>To prevent or investigate possible wrongdoing in connection with the Service</li>
                    <li>To protect the personal safety of users of the Service or the public</li>
                    <li>To protect against legal liability</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">5. Account and Data Deletion</h2>
                <p class="mb-4">
                    You have the right to request the deletion of your account and associated personal data at any time.
                </p>
                <ul class="list-disc pl-6 mb-4 space-y-1">
                    <li><strong>In the Mobile App:</strong> You can delete your account by navigating to <strong>Settings &gt; Delete Account</strong>.</li>
                    <li><strong>On the Website:</strong> You can request account deletion by navigating to your <strong>Manage Account</strong> page or by contacting our support team.</li>
                </ul>
                <p class="mb-4">
                    When you choose to delete your account, your personal identifying information (such as your name and email) will be anonymized or permanently removed from our active databases, and your authentication access will be immediately revoked. Please note that certain data, such as historical CRM records (e.g., leads or events) associated with a workspace you belonged to, may be retained by the workspace owner as per their data retention policies, but it will no longer be linked to your personal identity.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">6. Security of Data</h2>
                <p class="mb-4">
                    The security of your data is important to us, but remember that no method of transmission over the Internet, or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">7. Service Providers</h2>
                <p class="mb-4">
                    We may employ third party companies and individuals to facilitate our Service ("Service Providers"), to provide the Service on our behalf, to perform Service-related services or to assist us in analyzing how our Service is used.
                </p>
                <p class="mb-4">
                    These third parties have access to your Personal Data only to perform these tasks on our behalf and are obligated not to disclose or use it for any other purpose.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">8. Links to Other Sites</h2>
                <p class="mb-4">
                    Our Service may contain links to other sites that are not operated by us. If you click on a third party link, you will be directed to that third party's site. We strongly advise you to review the Privacy Policy of every site you visit.
                </p>
                <p class="mb-4">
                    We have no control over and assume no responsibility for the content, privacy policies or practices of any third party sites or services.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">9. Children's Privacy</h2>
                <p class="mb-4">
                    Our Service does not address anyone under the age of 18 ("Children").
                </p>
                <p class="mb-4">
                    We do not knowingly collect personally identifiable information from anyone under the age of 18. If you are a parent or guardian and you are aware that your Children has provided us with Personal Data, please contact us. If we become aware that we have collected Personal Data from children without verification of parental consent, we take steps to remove that information from our servers.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">10. Changes to This Privacy Policy</h2>
                <p class="mb-4">
                    We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.
                </p>
                <p class="mb-4">
                    We will let you know via email and/or a prominent notice on our Service, prior to the change becoming effective and update the "effective date" at the top of this Privacy Policy.
                </p>
                <p class="mb-4">
                    You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-semibold mb-3 text-gray-800">11. Contact Us</h2>
                <p class="mb-4">If you have any questions about this Privacy Policy, please contact us:</p>
                <ul class="list-disc pl-6 mb-4 space-y-1">
                    <li>By email: <a href="mailto:support@osfero.com" class="text-blue-600 hover:underline">support@osfero.com</a></li>
                </ul>
            </section>
        </div>
    </div>
</body>
</html>
