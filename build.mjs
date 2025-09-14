import * as esbuild from 'esbuild';

const ctx = await esbuild.context({
    entryPoints: [
        'assets/scripts/specific/admin.ts',
        // Ajoutez d'autres fichiers TypeScript ici si ils ont une dépendance NPM
    ],
    bundle: true,
    outdir: 'assets/scripts/specific/',
    outExtension: { '.js': '.js' },
    format: 'esm',
    target: 'es2020',
    sourcemap: true,
    minify: false, // true en production
    splitting: false,
    platform: 'browser',
    define: {
        'process.env.NODE_ENV': '"development"'
    },
});

// Mode watch pour le développement
if (process.argv.includes('--watch')) {
    console.log('Watching for changes...');
    await ctx.watch();
} else {
  // Build one-time
    await ctx.rebuild();
    await ctx.dispose();
    console.log('✅ Build complete!');
}
