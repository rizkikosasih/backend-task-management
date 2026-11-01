# AGENTS (Rule Workspace) untuk Repo: backend-task-management

Tujuan: menetapkan aturan agar agen (automated bots / copilots / CI agents) bertindak aman dan konsisten saat membuat perubahan terkait fitur OAuth2.

## Prinsip Umum
- Agen hanya boleh membuat PR di branch feature/* dan tidak langsung merge ke default branch.
- Semua PR dari agen harus berlabel `agent` dan `needs-review`.
- Agen harus menyertakan checklist testing & migrasi dalam body PR.

## Commit & PR Rules
- Commit message format: "<type>(<scope>): <brief description>"
  - type: feat|fix|chore|docs|test|ci
  - scope: oauth|auth|db|services
- PR title: "[agent] <type>: <short description>"
- PR description harus menyertakan:
  - daftar file yang diubah
  - perintah migrasi jika ada
  - instruksi rollback

## Migration Rules
- Agen tidak menjalankan migrate di production.
- Semua migrasi harus dibuat sebagai file baru (timestamped) dan tidak mengedit migrasi lama.
- Jika PR menambah/ubah migrasi, sertakan SQL preview & pengaruh skema (nullable, default).

## Secret Handling
- Agen tidak pernah menulis secrets ke file yang di-commit.
- Agen bisa menambahkan placeholder ke .env.example namun bukan nilai rahasia.

## Testing Requirements
- Minimal: unit tests untuk logic core (AuthService), integration test untuk controller (mock Socialite)
- Agen harus menjalankan test suite lokal/CI dan memastikan status `green` sebelum membuat PR.

## Security & Scanning
- Agen harus trigger SAST / dependency scan pada PR.
- Jika dependency baru (ex: socialite) ditambahkan, agen harus menambahkan note ke CHANGELOG dan memicu Dependabot review.

## Code Review & Merge
- Minimal 1 human reviewer must approve (backend owner / security reviewer)
- Merge hanya via merge commit after 1 approval and CI passing
