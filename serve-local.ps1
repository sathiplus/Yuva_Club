param(
  [int]$Port = 4177
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, $Port)
$listener.Start()
Write-Host "Serving $root at http://127.0.0.1:$Port/"

$types = @{
  ".html" = "text/html; charset=utf-8"
  ".css" = "text/css; charset=utf-8"
  ".js" = "application/javascript; charset=utf-8"
  ".svg" = "image/svg+xml"
  ".png" = "image/png"
  ".jpg" = "image/jpeg"
  ".jpeg" = "image/jpeg"
}

function Send-Response($stream, [int]$status, [string]$statusText, [byte[]]$body, [string]$contentType) {
  $header = "HTTP/1.1 $status $statusText`r`nContent-Type: $contentType`r`nContent-Length: $($body.Length)`r`nConnection: close`r`n`r`n"
  $headerBytes = [Text.Encoding]::ASCII.GetBytes($header)
  $stream.Write($headerBytes, 0, $headerBytes.Length)
  if ($body.Length -gt 0) {
    $stream.Write($body, 0, $body.Length)
  }
}

try {
  while ($true) {
    $client = $listener.AcceptTcpClient()
    try {
      $stream = $client.GetStream()
      $buffer = New-Object byte[] 8192
      $read = $stream.Read($buffer, 0, $buffer.Length)
      if ($read -le 0) { continue }

      $request = [Text.Encoding]::ASCII.GetString($buffer, 0, $read)
      $firstLine = ($request -split "`r?`n")[0]
      $parts = $firstLine -split " "
      if ($parts.Length -lt 2 -or $parts[0] -ne "GET") {
        Send-Response $stream 405 "Method Not Allowed" ([Text.Encoding]::UTF8.GetBytes("Method not allowed")) "text/plain; charset=utf-8"
        continue
      }

      $path = [Uri]::UnescapeDataString(($parts[1] -split "\?")[0].TrimStart("/"))
      if ([string]::IsNullOrWhiteSpace($path)) { $path = "index.html" }
      $path = $path -replace "/", [IO.Path]::DirectorySeparatorChar
      $target = [IO.Path]::GetFullPath((Join-Path $root $path))

      if (-not $target.StartsWith($root, [StringComparison]::OrdinalIgnoreCase)) {
        Send-Response $stream 403 "Forbidden" ([Text.Encoding]::UTF8.GetBytes("Forbidden")) "text/plain; charset=utf-8"
        continue
      }

      if ((Test-Path $target -PathType Container)) {
        $target = Join-Path $target "index.html"
      }

      if (-not (Test-Path $target -PathType Leaf)) {
        Send-Response $stream 404 "Not Found" ([Text.Encoding]::UTF8.GetBytes("Not found")) "text/plain; charset=utf-8"
        continue
      }

      $extension = [IO.Path]::GetExtension($target).ToLowerInvariant()
      $contentType = if ($types.ContainsKey($extension)) { $types[$extension] } else { "application/octet-stream" }
      Send-Response $stream 200 "OK" ([IO.File]::ReadAllBytes($target)) $contentType
    }
    finally {
      $client.Close()
    }
  }
}
finally {
  $listener.Stop()
}
