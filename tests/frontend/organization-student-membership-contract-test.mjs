import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');
const check = (condition, message) => { if (!condition) throw new Error(message); };

const org = read('organization-admin.php');
const student = read('portal.php');
const parent = read('parent.php');

check(org.includes('Invite a new student'), 'Organization Admin must have the new-student invitation form.');
check(org.includes('Request an existing student link'), 'Organization Admin must have the existing-student request form.');
check(org.includes('The response is intentionally neutral'), 'Organization Admin UI must explain the privacy-neutral response.');
check(org.includes('requestsForOrganization($organizationId)'), 'Organization requests must use authenticated organization scope.');
check(!org.includes('student_directory') && !org.includes('Browse students'), 'Organization Admin must not receive a student browser.');
check(student.includes('Accept') && student.includes('Decline'), 'Student must control membership acceptance.');
check(parent.includes('Approve') && parent.includes('Withdraw'), 'Parent must control approval and withdrawal.');

console.log('Organization student membership frontend contracts: PASS');
