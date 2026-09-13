// Lets `tsc --noEmit` resolve .vue imports when typechecking this package
// standalone. The apps use vue-tsc, which understands SFCs natively.
declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}
