export function getDppApprovalStorageKey(email) {
    return `kkn-tematik:dpp-approval:${email.trim().toLowerCase()}`;
}
