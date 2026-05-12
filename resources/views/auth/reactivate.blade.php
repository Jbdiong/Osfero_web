<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reactivate Account - Osfero</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full flex items-center justify-center">
    <div class="max-w-md w-full px-6 py-8 bg-white dark:bg-gray-800 shadow-md rounded-lg sm:px-10">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Account Deactivated</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Your global account is currently suspended or deactivated. 
            </p>
        </div>

        <div class="space-y-6">
            <p class="text-sm text-gray-700 dark:text-gray-300 text-center">
                Would you like to reactivate your account and continue using <strong>{{ Auth::user()->email }}</strong>?
            </p>

            <form action="{{ route('reactivate.process') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Yes, reactivate my account
                </button>
            </form>

            <form action="{{ route('reactivate.cancel') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    No, log me out
                </button>
            </form>
        </div>
    </div>
</body>
</html>
