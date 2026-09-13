import { initializeApp, type FirebaseApp } from 'firebase/app'
import {
  GoogleAuthProvider,
  createUserWithEmailAndPassword,
  getAuth,
  onAuthStateChanged,
  signInWithEmailAndPassword,
  signInWithPopup,
  signOut as firebaseSignOut,
  type Auth,
  type User as FirebaseUser,
} from 'firebase/auth'

// The ONLY module that imports the Firebase SDK (D-015). Apps never touch
// `firebase` directly — they call the auth store, which calls auth.service,
// which calls this. Keeping the SDK behind one seam is what lets the native
// shells swap in a different sign-in flow later without touching views.

let app: FirebaseApp | null = null

function config() {
  return {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
  }
}

/** True when the app was given a real Firebase config (mock mode ships none). */
export function isFirebaseConfigured(): boolean {
  const { apiKey, projectId, appId } = config()
  return Boolean(apiKey && projectId && appId)
}

function auth(): Auth {
  if (!isFirebaseConfigured()) {
    throw new Error(
      'Firebase is not configured — set VITE_FIREBASE_* in this app\'s .env (see .env.example).',
    )
  }

  app ??= initializeApp(config())
  return getAuth(app)
}

export async function signUpWithEmail(email: string, password: string): Promise<string> {
  const credential = await createUserWithEmailAndPassword(auth(), email, password)
  return credential.user.getIdToken()
}

export async function signInWithEmail(email: string, password: string): Promise<string> {
  const credential = await signInWithEmailAndPassword(auth(), email, password)
  return credential.user.getIdToken()
}

export async function signInWithGoogle(): Promise<string> {
  const credential = await signInWithPopup(auth(), new GoogleAuthProvider())
  return credential.user.getIdToken()
}

/**
 * A fresh ID token for the signed-in Firebase user, or null if there isn't one.
 * `forceRefresh` is what the 401 interceptor uses to re-exchange silently.
 */
export async function getIdToken(forceRefresh = false): Promise<string | null> {
  if (!isFirebaseConfigured()) return null

  const current = auth().currentUser
  return current ? current.getIdToken(forceRefresh) : null
}

export async function signOutFirebase(): Promise<void> {
  if (!isFirebaseConfigured()) return
  await firebaseSignOut(auth())
}

/** Fires once Firebase has restored (or failed to restore) a session on boot. */
export function onFirebaseReady(callback: (user: FirebaseUser | null) => void): () => void {
  if (!isFirebaseConfigured()) {
    callback(null)
    return () => {}
  }

  return onAuthStateChanged(auth(), callback)
}

export type { FirebaseUser }
