export default {
  extends: ['@commitlint/config-conventional'],
  defaultIgnores: false,
  plugins: [{
    rules: {
      'scope-single': ({ scope }) => [
        !scope || !/[\\/,]/u.test(scope),
        'scope must identify a single module or responsibility',
      ],
    },
  }],
  rules: {
    'type-enum': [2, 'always', ['feat', 'fix', 'refactor', 'test', 'docs', 'build', 'ci', 'perf', 'chore']],
    'scope-empty': [2, 'never'],
    'scope-enum': [2, 'always', ['identity', 'recipes', 'community', 'media', 'runtime',
      'security', 'ci', 'docs', 'agents', 'architecture', 'inventory', 'skills']],
    'scope-single': [2, 'always'],
    'subject-case': [0],
    'breaking-change-exclamation-mark': [2, 'always'],
  },
};
