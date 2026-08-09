export const state = {
  user: null,
  route: location.hash.replace(/^#/, '') || 'dashboard',
};
