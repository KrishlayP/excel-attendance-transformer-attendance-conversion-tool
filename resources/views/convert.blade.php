<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Convert NEW → OLD Matrix</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          boxShadow: {
            soft: '0 10px 25px -10px rgba(0,0,0,0.15)',
          },
        },
      },
    };
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 text-slate-800 antialiased">
  <div class="mx-auto max-w-3xl px-4 py-10">
    <!-- Header -->
    <header class="mb-8 text-center">
      <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 shadow-soft">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
          <path d="M3 5.25A2.25 2.25 0 0 1 5.25 3h9A2.25 2.25 0 0 1 16.5 5.25v1.5h2.25A2.25 2.25 0 0 1 21 9v9.75A2.25 2.25 0 0 1 18.75 21H8.25A2.25 2.25 0 0 1 6 18.75V16.5H5.25A2.25 2.25 0 0 1 3 14.25v-9Z"/>
          <path d="M6 16.5h2.25A2.25 2.25 0 0 0 10.5 14.25V12H6v4.5Z" class="opacity-70"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
        NEW Attendance (block/vertical) → OLD Matrix (In / Out / Total)
      </h1>
      <p class="mt-2 text-sm text-slate-600">
        Upload your <span class="font-medium">NEW</span> format Excel report and get a clean <span class="font-medium">OLD matrix</span> layout.
      </p>
    </header>

    <!-- Card -->
    <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-200">
      <!-- Tips -->
      <div class="mb-6 flex items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 text-sm text-indigo-900">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mt-0.5 h-5 w-5 text-indigo-600">
          <path fill-rule="evenodd" d="M9.401 3.004a2.25 2.25 0 0 1 3.198 0l8.397 8.397a2.25 2.25 0 0 1 0 3.198l-8.397 8.397a2.25 2.25 0 0 1-3.198 0L1.004 14.599a2.25 2.25 0 0 1 0-3.198L9.401 3.004Zm2.599 4.996a.75.75 0 0 0-1.5 0v6a.75.75 0 0 0 1.5 0v-6Zm-.75 9a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd" />
        </svg>
        <p>
          Tip: Use reports like <span class="font-medium">“Daily Attendance Report (Summary Report)”</span> exported from your HRMS.
        </p>
      </div>

      <!-- Form -->
      <form action="{{ route('convert.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700">Upload NEW format (.xls / .xlsx)</label>

          <!-- Dropzone-style input -->
          <label for="new_file" class="group relative block cursor-pointer overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 transition hover:border-indigo-400 hover:bg-indigo-50 focus-within:ring-2 focus-within:ring-indigo-500">
            <div class="pointer-events-none flex items-center justify-center gap-3">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-slate-500 group-hover:text-indigo-600">
                <path d="M3.75 6A2.25 2.25 0 0 1 6 3.75h12A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6Z" />
                <path d="M8.47 12.53a.75.75 0 0 0 1.06 0L11.25 10.81v5.44a.75.75 0 1 0 1.5 0v-5.44l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 0 0 0 1.06Z" />
              </svg>
              <div class="text-center">
                <p id="fileLabel" class="text-sm font-medium text-slate-700">Drop your Excel here or click to browse</p>
                <p class="text-xs text-slate-500">Accepted: .xls, .xlsx — Max 20 MB</p>
              </div>
            </div>
            <input id="new_file" name="new_file" type="file" accept=".xls,.xlsx" required class="sr-only" />
          </label>
        </div>

        <!-- Actions -->
        <div class="flex flex-col-reverse items-center justify-between gap-4 sm:flex-row">
          <p class="text-xs text-slate-500">Your file is processed securely and never stored longer than needed.</p>
          <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-2 h-5 w-5">
              <path d="M5.25 3A2.25 2.25 0 0 0 3 5.25v13.5A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V8.25A2.25 2.25 0 0 0 18.75 6H12l-2.25-3H5.25Z" />
            </svg>
            Convert
          </button>
        </div>

        <!-- Errors -->
        @if ($errors->any())
          <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            <div class="mb-1 flex items-center gap-2 font-medium">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-red-600">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm10.5-5.25a.75.75 0 0 0-1.5 0v6a.75.75 0 0 0 1.5 0v-6Zm-1.5 9.75a1.125 1.125 0 1 0 2.25 0 1.125 1.125 0 0 0-2.25 0Z" clip-rule="evenodd" />
              </svg>
              <span>There were some issues with your upload:</span>
            </div>
            <ul class="ml-5 list-disc text-sm">
              @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif
      </form>
    </div>

    <!-- Footer note -->
    <p class="mt-6 text-center text-xs text-slate-500">
      Need help mapping columns? <span class="font-medium text-slate-700">Share a sample</span> and we’ll guide you.
    </p>
  </div>

  <script>
    const input = document.getElementById('new_file');
    const fileLabel = document.getElementById('fileLabel');
    input?.addEventListener('change', () => {
      const name = input.files?.[0]?.name;
      if (name) fileLabel.textContent = name;
    });
  </script>
</body>
</html>
