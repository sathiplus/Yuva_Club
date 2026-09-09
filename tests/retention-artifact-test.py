"""Build the actual release ZIP and dry-run its scheduled entry, without tools/."""
import json
import os
import pathlib
import subprocess
import sys
import tempfile
import zipfile

source = pathlib.Path(__file__).resolve().parents[1]
php = sys.argv[1] if len(sys.argv) > 1 else 'php'
with tempfile.TemporaryDirectory(prefix='yuva-retention-artifact-') as temporary:
    root = pathlib.Path(temporary).resolve()
    package = root / 'application'
    subprocess.run([sys.executable, str(source/'tools/build-release-artifact.py'),
        '--commit', 'HEAD', '--output', str(root/'release.zip'),
        '--inventory', str(root/'inventory.json'),
        '--exclusions', str(source/'tools/release-artifact-exclusions.txt')], cwd=source, check=True)
    with zipfile.ZipFile(root/'release.zip') as archive:
        names = archive.namelist()
        assert 'backend/jobs/media-retention.php' in names
        assert 'App_Data/jobs/triggered/yuva-media-retention/run.php' in names
        assert not any(name.startswith('tools/') for name in names)
        assert all(not pathlib.PurePosixPath(name).is_absolute() and '..' not in pathlib.PurePosixPath(name).parts for name in names)
        archive.extractall(package)
    env = {k:v for k,v in os.environ.items() if not k.startswith(('DB_', 'WEBSITE_', 'YUVA_TEST_DB_'))}
    env.update(APP_ENV='test', DB_DRIVER='mysql', YUVA_MEDIA_RETENTION_DAYS='30')
    for setting, directory in [('YUVA_PORTAL_DATA_PATH','data'),('YUVA_PORTAL_UPLOADS_PATH','uploads'),('YUVA_SUBMISSIONS_PATH','submissions')]:
        path = root/directory
        path.mkdir()
        env[setting] = str(path)
    sentinel = root/'uploads'/'preserve-unreferenced-media.txt'
    sentinel.write_text('synthetic fixture only')
    command = [php, str(package/'App_Data/jobs/triggered/yuva-media-retention/run.php')]
    result = subprocess.run(command+['--dry-run'], cwd=package, env=env, capture_output=True, text=True, check=True)
    summary = json.loads(result.stdout)
    assert summary['mode']=='dry-run' and summary['retention_days']==30 and summary['deleted']==0 and summary['status']=='ok'
    assert sentinel.read_text()=='synthetic fixture only'
    rejected = subprocess.run(command+['--execute','--dry-run'], cwd=package, env=env, capture_output=True, text=True)
    assert rejected.returncode==2, 'Conflicting destructive/dry-run flags must fail before bootstrap'
    print('PASS packaged scheduled job invokes 30-day dry-run with tools excluded; no deletion; conflicting flags rejected')
